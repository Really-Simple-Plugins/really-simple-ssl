<?php
defined('ABSPATH') or die();
if ( !class_exists('rsssl_vulnerabilities_pro') && class_exists('rsssl_vulnerabilities') ) {
    class rsssl_vulnerabilities_pro extends rsssl_vulnerabilities
    {
        public $max_update_attempt_count = 2;
		public $mail_queue = [];
		public $updated_plugin_versions = [];

	    /**
	     * value in seconds. Should be shorter than runtime
	     * @var array
	     */
	    public $warnTime = 24 * HOUR_IN_SECONDS;
        /**
         * value in seconds
         * @var array
         */
        public $runTime = 48 * HOUR_IN_SECONDS;

        /**
         * value in seconds. Recommended to be same as runTime.
         * @var array
         */
        private $postponeTime = 24 * HOUR_IN_SECONDS;

        private $taken_measures = [];

        public function __construct()
        {
	        parent::__construct();
	        add_action('rsssl_every_three_hours_cron', array($this, 'run_cron_pro'), 20);
        }

		public function run_cron_pro(){
			if ( RSSSL_PRO()->licensing->license_is_valid() ) {
				//prevent duplicate instantiation
				if ( get_transient('rsssl_vulnerabilities_cron_active') ) {
					return;
				}
				set_transient('rsssl_vulnerabilities_cron_active', true,  MINUTE_IN_SECONDS);
//				delete_option('rsssl_measures_list');
				//we get the current list
				$this->taken_measures = get_option('rsssl_measures_list', []);
				//now we check the list if there are identifiers that are not in the list anymore
				$this->cleanup_list();
				$this->generate_measures_list();
				//now we start the process for the quarantine
				$this->handle_measures();
				$this->send_mail_queue();

				//drop all items that have the flag 'fixed'
				$this->taken_measures = array_filter($this->taken_measures, static function($item){
					return !isset($item['fixed']);
				});
				//we save the list
				update_option('rsssl_measures_list', $this->taken_measures);
				delete_transient('rsssl_vulnerabilities_cron_active' );
			}
		}

        /**
         * This function creates a list for all the measures that need to be taken
         *
         * @return void
         */
        public function generate_measures_list(): void {
			$count = count($this->workable_plugins);
			if ($count===0){
				return;
			}
            //we filter through our list of vulnerabilities, and add the ones that are quarantined to the list.
            foreach ($this->workable_plugins as $plugin) {
                //if there is a risk level or the plugin is already set we skip it.
                if ( !isset($plugin['risk_level']) ) {
                    continue;
                }

                //now we check if the risk level is the same as the one we want to quarantine.
				$measure = $this->get_highest_measure($plugin['risk_level']);
				if ( !$measure ) {
					continue;
				}

				//check if the risk level of this plugin requires an action
				//search index by rss_identifier
	            $key = $this->get_key_by_identifier($plugin['rss_identifier']);
	            if ( $key === -1 ) {
                    $this->taken_measures[] = $this->generate_measure($plugin, $measure);
                } else {
					$existing_measure = $this->taken_measures[$key];
	                $this->taken_measures[$key] = $this->generate_measure($plugin, $measure, $existing_measure);
                }
            }
        }

	    /**
	     * @param string $risk_level
	     *
	     * @return string
	     */
		private function get_highest_measure( string $risk_level){
			//get the risk level in integers.
			$int_risk_level = $this->risk_levels[$risk_level];
			/**
			 * e.g Complianz has risk level medium. 2.
			 * force update is set to medium $force_update_from_level_int = 2
			 * quarantine is set to critical => $quarantine_from_level_int = 4
			 * In this case, the risk is medium so, the highest measure is force_update. .
			 */

			$quarantine_from_level = get_option( 'rsssl_quarantine');
			$quarantine_from_level_int = $this->risk_levels[ $quarantine_from_level ] ?? false;
			$force_update_from_level = get_option( 'rsssl_force_update');
			$force_update_from_level_int = $this->risk_levels[ $force_update_from_level ] ?? false;
			if ( $quarantine_from_level_int && $int_risk_level >= $quarantine_from_level_int ) {
				return 'quarantine';
			}
			if ( $force_update_from_level_int && $int_risk_level >= $force_update_from_level_int ) {
				return 'force_update';
			}

			return false;
		}

        /**
         * This generates a measure array for the list in here you can set values needed for actions.
         *
         * @param $vulnerability
         * @param string $measure
         * @param bool|array $existing_measure
         *
         * @return array
         */
        private function generate_measure($vulnerability, string $measure, $existing_measure = false ): array
        {
			if ( is_array($existing_measure) ) {
				$existing_measure['measure'] = $measure;
				return $existing_measure;
			}
            //we generate the measure array
            return [
                'plugin_name' => $vulnerability['Name'],
                'text_domain' => $vulnerability['TextDomain'],//deprecated, not used anymore
                'plugin_slug' => $vulnerability['Slug'],
                'type' => $vulnerability['type'],
                'measure' => $measure,
                'risk' => $vulnerability['risk_level'],
                'identifier' => $vulnerability['rss_identifier'],
                'start_date' => time(),
                'end_date' => time() + $this->runTime,
                'attempts' => 0,
            ];
        }

        /**
         * This executes the quarantine option with all it's needed steps and actions.
         *
         * @return void
         */
        private function handle_measures(): void {
	        if ( ! rsssl_admin_logged_in() ) {
		        return;
	        }

            //if there are no plugins in the list we skip it.
            if (empty($this->taken_measures)) {
				return;
            }
            foreach ($this->taken_measures as $key => $plugin) {
	            if ( isset($plugin['fixed']) ) {
		            continue;
	            }
				if ( isset($plugin['stopped']) ) {
					continue;
				}
	            if ( isset($plugin['quarantined']) ) {
		            continue;
	            }

	            $measure = $plugin['measure'];
				$plugin_name = $plugin['plugin_name'];
                if ( $this->is_warn_time($plugin) && !$this->is_action_time($plugin) ) {
                    //if first email is not sent, send it
                    if ( !isset($plugin['update_scheduled_email_sent']) ) {
	                    $this->add_to_mail_queue('update_scheduled', $plugin_name);
                        $this->taken_measures[$key]['update_scheduled_email_sent'] = true;
                    }
                }

                //check if current time is after end date
                if ( $this->is_action_time($plugin) ) {
                    //first we check if there is an update available
                    //we try to update the plugin 3 times
					if ($plugin['type'] === 'plugin' ) {
						$new_version = $this->updatePlugin( $plugin );
					} else {
						$new_version = $this->updateTheme( $plugin );
					}

                    if ( !$new_version ) {
	                    $attempt_count = $plugin['attempts'];
						//we don't want an update failed mail for the last attempt, as it will also send a pre quarantine mail .
						if ( !isset($plugin['update_failed_email_sent']) && $attempt_count<$this->max_update_attempt_count-1 ) {
							$this->add_to_mail_queue('update_failed', $plugin_name);
							$this->taken_measures[$key]['update_failed_email_sent'] = true;
						}
	                    //if the update fails we increase the attempts and postpone the end date
                        $this->taken_measures[$key]['attempts'] = $attempt_count + 1;
						$this->taken_measures[$key]['end_date'] = time() + $this->postponeTime;
						//if this is the third attempt, we send an email
	                    if ( $this->taken_measures[$key]['attempts']===$this->max_update_attempt_count ) {
		                    if ( $measure === 'quarantine' ) {
			                    $this->add_to_mail_queue('quarantine_scheduled', $plugin_name);
		                    } else {
								$this->add_to_mail_queue('end_process', $plugin_name);
			                    $this->taken_measures[$key]['stopped'] = true;
			                    continue;
		                    }
	                    }
                    } else {
	                    $this->updated_plugin_versions[$plugin_name] = $new_version;
	                    if ( !isset($plugin['update_success_email_sent']) ) {
		                    $this->add_to_mail_queue('update_success', $plugin_name);
		                    $this->taken_measures[$key]['update_success_email_sent'] = true;
	                    }
						//set this to fixed, instead of unsetting the key. This ensures that no other attempts are made during this run.
	                    $this->taken_measures[$key]['fixed'] = true;
                        //skip to next plugin
                        continue;
                    }

					if ( $measure === 'quarantine' ) {
						if ( $this->taken_measures[$key]['attempts'] > $this->max_update_attempt_count && !isset($this->taken_measures[ $key ]['quarantined']) ) {
							//We move the plugin to quarantine
							$this->moveToQuarantine( $plugin );
							$this->taken_measures[ $key ]['quarantined'] = true;
							if ( !isset($plugin['quarantine_email_sent']) ) {
								$this->add_to_mail_queue( 'quarantine', $plugin_name );
								$this->taken_measures[$key]['quarantine_email_sent'] = true;
							}
						}
					}
                }
            }
        }

	    /**
	     * Add a plugin to an action in the mail queue
	     *
	     * @param string $action
	     * @param string $plugin_name
	     *
	     * @return void
	     */
		public function add_to_mail_queue(string $action, string $plugin_name) {
			if ( ! isset( $this->mail_queue[ $action ] ) ) {
				$this->mail_queue[ $action ] = [];
			}

			if ( ! in_array( $plugin_name, $this->mail_queue[ $action ], true ) ){
				$this->mail_queue[$action][] = $plugin_name;
			}
		}

	    /**
	     * Send all mails in the queueu
	     * @return void
	     */
		private function send_mail_queue(): void {
			if ( ! rsssl_admin_logged_in() ) {
				return;
			}

			if ( !is_array($this->mail_queue) ) {
				return;
			}

			foreach ($this->mail_queue as $mail_type => $plugins) {
				//concatenate $plugins array
				$string = implode(", ", $plugins);
				//get first plugin from array
				$plugin = array_shift($plugins);
				$count = count($plugins);
				unset($this->mail_queue[$mail_type]);
				switch ($mail_type) {
					case 'update_scheduled':
						$this->sendUpdateScheduledMail($plugin, $count);
						break;
					case 'update_failed':
						$this->sendUpdateFailedEmail($plugin, $count);
						break;
					case 'update_success':
						$this->sendUpdateSuccessMail($plugin, $count);
						break;
					case 'quarantine_scheduled':
						$this->sendQuarantineScheduledMail($plugin, $count);
						break;
					case 'quarantine':
						$this->sendQuarantineEmail($plugin, $count);
						break;
					case 'end_process':
						$this->sendEndProcessMail($plugin, $count);
						break;
				}
			}
		}

	    /**
	     * check if current time is x seconds before end date you can set this in the constructor
	     *
	     * @param array $plugin
	     *
	     * @return bool
	     */
		private function is_warn_time(array $plugin): bool {
			$endTime = $plugin['end_date'];
			$now = time()+5*60;
			$warn = $this->warnTime;
			$left = round((($endTime - $warn) - $now)/60,1);
			if ($left < 0) {
				$left = 0;
			}
			return ($endTime - $warn) < $now;
		}

	    /**
	     * check if current time is x seconds before end date you can set this in the constructor
	     *
	     * @param array $plugin
	     *
	     * @return bool
	     */
		private function is_action_time( array $plugin): bool {
			$endTime = $plugin['end_date'];
			$now = time() +9.8*60;
			return $endTime < $now;
		}

        /**
         * Cleans up unnecessary clutter based on actual vulnerabilities present.
         *
         * @return void
         */
        private function cleanup_list(): void {
			//get list of plugin files
	        //filter from $this->>workable_plugins all plugins where the vulnerable key = true
	        $workable_plugins = array_filter($this->workable_plugins, static function ($plugin) {
		        return $plugin['vulnerable'] === true;
	        });
	        $slugs = array_column($workable_plugins, 'Slug');
            //we check the taken measures
            foreach ($this->taken_measures as $key => $plugin) {
                if (isset($plugin['plugin_slug'])) {
	                //if not, we remove it from the list
	                if ( ! in_array( $plugin['plugin_slug'], $slugs ) && isset( $this->taken_measures[ $key ] ) ) {
	                    unset($this->taken_measures[$key]);
	                }
                }
            }
        }

        /**
         * This function checks if the identifier is in the list
         *
         * @param string $rssl_identifier
         *
         * @return int
         */
        private function get_key_by_identifier(string $rssl_identifier): int
        {
            if ( is_array($this->taken_measures) ) {
                foreach ($this->taken_measures as $key => $measure) {
                    if ( $measure['identifier'] === $rssl_identifier) {
                        return $key;
                    }
                }
            }
            return -1;
        }

        /**
         * fetches the version installed of a plugin
         *
         * @param $plugin
         * @return string
         */
        private function getCurrentVersion($plugin): string
        {
	        $plugin_slug = $plugin['plugin_slug'];
            $current_version = '';
            array_filter($this->workable_plugins, static function ($plugin_item) use ($plugin_slug, &$current_version) {
				if ($plugin_item['Slug'] === $plugin_slug) {
                    $current_version = $plugin_item['Version'];
                }
            });
            return $current_version;
        }

	    /**
	     * updates a plugin silently
	     *
	     * @param array $plugin
	     * @return bool|string
	     */
	    private function updateTheme(array $plugin)
	    {
		    if ( ! rsssl_admin_logged_in() ) {
			    return false;
		    }
		    $theme_slug = $plugin['plugin_slug'];
		    //now we fetch the latest version from the WordPress repository
		    $latest_version = $this->getLatestThemeVersion($plugin);
		    $current_version = $this->getCurrentVersion($plugin);

		    //now we compare the versions
		    if ( rsssl_version_compare($current_version, $latest_version, '<') ) {
			    if ( !$theme_slug ) {
				    return false;
			    }
			    //we update the plugin
			    require_once ABSPATH . 'wp-admin/includes/file.php';
			    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			    include_once ABSPATH . 'wp-admin/includes/theme-install.php';
			    $upgrader = new Theme_Upgrader(new Automatic_Upgrader_Skin(
				    array(
					    'title' => __('Upgrading...', 'really-simple-ssl'),
					    'type' => 'auto',
					    'nonce' => wp_create_nonce('upgrade-theme_' . $theme_slug),
				    )
			    ));
			    //we will update the plugin but silent
			    $result = $upgrader->bulk_upgrade(array($theme_slug), array(
				    'clear_update_cache' => true,
				    'clear_update_transient' => true,
			    ));

			    if ( is_array($result) && count($result) > 0) {
				    //also we force download components
				    $this->download_plugin_vulnerabilities();
				    return $latest_version;
			    }

			    // The upgrade failed
			    return false;
		    }

		    return false;//no update availabe
	    }

        /**
         * updates a plugin silently
         *
         * @param array $plugin
         * @return bool|string
         */
        private function updatePlugin(array $plugin )
        {
	        if ( ! rsssl_admin_logged_in() ) {
		        return false;
	        }

            //now we fetch the latest version from the WordPress repository
            $latest_version = $this->getLatestVersion($plugin);
	        $current_version = $this->getCurrentVersion($plugin);
            //now we compare the versions
            if ( rsssl_version_compare($current_version, $latest_version, '<') ) {
                $plugin_file = $plugin['plugin_slug'];
                if ( !$plugin_file ) {
					return false;
                }
                //we update the plugin
	            require_once ABSPATH . 'wp-admin/includes/file.php';
	            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	            include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
                $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin(
                    array(
                        'title' => __('Upgrading...', 'really-simple-ssl'),
                        'type' => 'auto',
                        'nonce' => wp_create_nonce('upgrade-plugin_' . $plugin_file),
                    )
                ));
                //we will update the plugin but silent
                $result = $upgrader->bulk_upgrade(array($plugin_file), array(
                    'clear_update_cache' => true,
                    'clear_update_transient' => true,
                ));

                if ( is_array($result) && count($result) > 0) {
                    //also we force download components
                    $this->download_plugin_vulnerabilities();
                    return $latest_version;
                }

				// The upgrade failed
	            return false;
            }

	        return false;//no update availabe
        }

        /**
         * Renames the plugin folder to .identifier
         *
         * @param $plugin
         * @return void
         */
        private function moveToQuarantine($plugin): void
        {
	        if ( ! rsssl_admin_logged_in() ) {
		        return;
	        }

            $workingDirectory = '';
			$new = '-rsssl-q-' . rsssl_generate_random_string(6);
            if ($plugin['type'] === 'plugin') {
                $workingDirectory = WP_PLUGIN_DIR . '/' . $plugin['plugin_slug'];
				//if $plugin_slug contains a directory, rename that directory.
                //This is the case for plugins like woocommerce/woocommerce.php
                if (strpos($plugin['plugin_slug'], '/') !== false) {
					$workingDirectory = WP_PLUGIN_DIR . '/' . dirname($plugin['plugin_slug']);
	            }
            }

            if ($plugin['type'] === 'theme') {
				//theme is already a directory.
                $workingDirectory = get_theme_root() . '/' . $plugin['plugin_slug'];
            }

			//if $workingDirectory is a directory, rename that directory.
	        if ( is_dir($workingDirectory) ) {
				rename($workingDirectory, $workingDirectory . $new );
	        } else if (is_file($workingDirectory) ) {
				//if $workingDirectory is a file, rename that file.
		        //get the filename from the path
		        $filename = basename($workingDirectory);
				$filename_no_extension = str_replace('.php','', $filename);
		        $new_filename = $filename_no_extension . $new . '.php';
		        $new_working_directory = str_replace($filename,$new_filename,$workingDirectory);
				rename($workingDirectory, $new_working_directory);
	        }
        }

        /**
         * Fetches the latest version of the plugin if not it returns the current version
         *
         * @param array $plugin
         * @return string
         */
        private function getLatestVersion(array $plugin): string
        {
            $current_version = $this->getCurrentVersion($plugin);
			$plugin_slug = $plugin['plugin_slug'];
            $plugin_data = get_site_transient('update_plugins');
            $plugin_data = $plugin_data ? array_filter($plugin_data->response, static function ($plugin) use ($plugin_slug) {
                return $plugin->plugin === $plugin_slug;
            }) : [];
            $plugin_data = array_values($plugin_data);   //reindex the array
            if (!$plugin_data) {
                return $current_version;
            }
            $plugin_data = $plugin_data[0];

            return $plugin_data->new_version;
        }


	    /**
	     * @param array $plugin
	     *
	     * @return string
	     */
        private function getLatestThemeVersion( array $plugin ): string {
	        $slug = $plugin['plugin_slug'];
            $current_version = $this->getCurrentVersion($plugin);
            $theme_data = get_site_transient('update_themes');
            $theme_data = array_filter($theme_data->response, function ($theme) use ($slug) {
                return $theme['theme'] === $slug;
            });
            $theme_data = array_values($theme_data);   //reindex the array
            if (!$theme_data) {
                return $current_version;
            }
            $theme_data = $theme_data[0];
            return $theme_data['new_version'];
        }

	    /**
	     * @param string $plugin
	     * @param int    $count
	     *
	     * @return void
	     */
        private function sendUpdateScheduledMail( string $plugin, int $count): void {
            $mailer = new rsssl_mailer();
            $mailer->subject = sprintf(__("Update Alert: %s.", "really-simple-ssl"), $this->site_url() );
			$mailer->title = sprintf(__("%s, Force Update: Scheduled.", "really-simple-ssl"), $this->date() );
	        if ( $count===1 ) {
	            $mailer->message = sprintf( __( "An automatic update for %s has been scheduled due to the discovery of a vulnerability on %s", "really-simple-ssl" ), $plugin, $this->domain() );
            } else {
	            $mailer->message = sprintf( __( "Several automatic updates for components have been scheduled due to the discovery of vulnerabilities on %s.", "really-simple-ssl" ), $this->domain() );
            }
			$hours = round((($this->max_update_attempt_count+1) * $this->warnTime) / HOUR_IN_SECONDS);
            $mailer->warning_blocks = [
                [
                    'title' => sprintf( __("Force Update: %s hours", "really-simple-ssl") , $hours ),
                    'message' => sprintf(__("We will initiate %s automatic update cycles, in the next %s hours, to mitigate available vulnerabilities.", "really-simple-ssl"), $this->max_update_attempt_count+1, $hours) .' '.
                                 __("Please double-check if your website is working as expected.","really-simple-ssl") .' '.
                                 sprintf(__('Get more information from the Really Simple SSL dashboard on %s'), $this->domain() ),
                    'url' => 'https://really-simple-ssl.com/manual/vulnerabilities#updates',
                ],
            ];
            $mailer->send_mail();
        }

	    /**
	     * Sends an email to the admin when a plugin has been updated or failed to update
	     *
	     * @param string $plugin
	     * @param int    $count
	     *
	     */
	    private function sendUpdateFailedEmail( string $plugin, int $count): void {
		    $mailer = new rsssl_mailer();
		    $mailer->subject = sprintf(__("Update Alert: %s", "really-simple-ssl" ), $this->site_url() );
		    $mailer->title = sprintf(__("%s, Force Update: Failed", "really-simple-ssl"), $this->date());
		    if ( $count===1 ) {
			    $mailer->message = sprintf(__("An automatic update for %s failed on %s.", "really-simple-ssl"), $plugin, $this->domain() );
		    } else {
			    $mailer->message = sprintf(__("Several automatic updates for vulnerable components, scheduled on %s, have failed.", "really-simple-ssl"), $this->domain() );
		    }

		    $hours = round(($this->max_update_attempt_count * $this->warnTime) / HOUR_IN_SECONDS);
		    $mailer->warning_blocks = [
			    [
				    'title' =>  __("Force Update: failed", "really-simple-ssl") ,
				    'message' => sprintf(__("We will initiate %s automatic update cycles, in the next %s hours, to mitigate available vulnerabilities.", "really-simple-ssl"), $this->max_update_attempt_count, $hours) .' '.
				                 __("Please double-check if your website is working as expected.","really-simple-ssl") .' '.
				                 sprintf(__('Get more information from the Really Simple SSL dashboard on %s'), $this->domain() ),
				    'url' => 'https://really-simple-ssl.com/manual/vulnerabilities#updates',
			    ],
		    ];

		    $mailer->send_mail();
	    }
	    /**
	     * Sends an email to the admin when a plugin has been updated or failed to update
	     *
	     * @param string $plugin
	     * @param int    $count
	     *
	     */
	    private function sendUpdateSuccessMail( string $plugin, int $count): void {
		    $mailer = new rsssl_mailer();
		    $mailer->subject = sprintf(__("Update Alert: %s", "really-simple-ssl" ), $this->site_url() );
		    $mailer->title = sprintf(__("%s, Force Update: Successful", "really-simple-ssl"), $this->date());
		    if ( $count===1 ) {
			    $mailer->message = sprintf(__("An automatic update for %s has been successful on %s.", "really-simple-ssl"), $plugin, $this->domain() );
		    } else {
			    $mailer->message = sprintf(__("Several automatic updates for vulnerable components, scheduled on %s, have been successful.", "really-simple-ssl"), $this->domain() );
		    }

		    $mailer->warning_blocks = [
			    [
				    'title' =>  __("Verify your website", "really-simple-ssl") ,
				    'message' => __("Please double-check if your website is working as expected.","really-simple-ssl") .' '.
				                 sprintf(__('Get more information from the Really Simple SSL dashboard on %s'), $this->domain() ),
				    'url' => 'https://really-simple-ssl.com/manual/vulnerabilities#updates',
			    ],
		    ];

		    $mailer->send_mail();
	    }


	    /**
	     * @param string $plugin
	     * @param int    $count
	     *
	     * @return void
	     */
		private function sendEndProcessMail( string $plugin, int $count): void {
			$mailer = new rsssl_mailer();
			$mailer->subject = sprintf(__("Update Alert: %s", "really-simple-ssl" ), $this->site_url() );
			$mailer->title = sprintf(__("%s, Force Update: End Process", "really-simple-ssl"), $this->date());
			if ( $count===1 ) {
				$mailer->message = sprintf(__("An automatic update for %s failed on %s.", "really-simple-ssl"), $plugin, $this->domain() );
				$mailer->message .= ' '. sprintf(__("This is the end of the update cycle, we recommend manually removing %s.", "really-simple-ssl"), $plugin);
			} else {
				$mailer->message = sprintf(__("Several automatic updates for vulnerable components, scheduled on %s, have failed.", "really-simple-ssl"), $this->domain() );
				$mailer->message .= ' '. __("This is the end of the update cycle, we recommend manually removing vulnerable components.", "really-simple-ssl");
			}

			$mailer->message .= ' '. __("You can also use our ‘Quarantine’ option to automate this process in the future.","really-simple-ssl");
			$mailer->warning_blocks = [
				[
					'title' =>  __("Verify your website", "really-simple-ssl") ,
					'message' => __("Please double-check if your website is working as expected.","really-simple-ssl") .' '.
					             sprintf(__('Get more information from the Really Simple SSL dashboard on %s'), $this->domain() ),
					'url' => 'https://really-simple-ssl.com/manual/vulnerabilities#updates',
				],
			];

			$mailer->send_mail();
        }

	    /**
	     * Checks sends a pre warning email if the plugin is marked for quarantine
	     *
	     * @param string $plugin
	     * @param int    $count
	     */
	    private function sendQuarantineScheduledMail( string $plugin, int $count): void {
		    $mailer = new rsssl_mailer();
		    $mailer->subject = sprintf(__("Quarantine Alert: %s", "really-simple-ssl" ), $this->site_url() );
		    $mailer->title = sprintf(__("%s, Quarantine: Scheduled", "really-simple-ssl"), $this->date());
		    if ( $count === 1 ) {
			    $mailer->message = sprintf(__("A quarantine for %s scheduled on %s.", "really-simple-ssl"), $plugin, $this->domain() );
		    } else {
			    $mailer->message = sprintf(__("Several vulnerable components scheduled for update on %s, have failed.", "really-simple-ssl"), $this->domain() );
		    }

		    $hours = round($this->postponeTime / HOUR_IN_SECONDS);
		    $mailer->warning_blocks = [
			    [
				    'title' =>  sprintf(__("Quarantine in %s hours", "really-simple-ssl"),$hours) ,
				    'message' => sprintf(__("We will initiate a quarantine cycle in the next %s hours to mitigate available vulnerabilities.", "really-simple-ssl"), $hours) .' '.
				                 __("Please double-check if your website is working as expected.","really-simple-ssl") .' '.
				                 sprintf(__('Get more information from the Really Simple SSL dashboard on %s'), $this->domain() ),
				    'url' => 'https://really-simple-ssl.com/manual/vulnerabilities#quarantine',
			    ],
		    ];

		    $mailer->send_mail();
	    }

	    /**
	     * Email when a plugin has been quarantined
	     *
	     * @param string $plugin
	     * @param int    $count
	     *         *
	     *
	     * @return void
	     */
        private function sendQuarantineEmail( string $plugin, int $count): void {
	        $mailer = new rsssl_mailer();
	        $mailer->subject = sprintf(__("Quarantine Alert: %s", "really-simple-ssl" ), $this->site_url() );
	        $mailer->title = sprintf(__("%s, Quarantine: Successful", "really-simple-ssl"), $this->date());
	        if ( $count === 1 ) {
		        $mailer->message = sprintf(__("A quarantine for %s has been successful on %s.", "really-simple-ssl"), $plugin, $this->domain() );
	        } else {
		        $mailer->message = sprintf(__("Several vulnerable components quarantined on %s.", "really-simple-ssl"), $this->domain() );
	        }

	        $mailer->warning_blocks = [
		        [
			        'title' =>  __("Verify your website", "really-simple-ssl") ,
			        'message' => __("Please double-check if your website is working as expected.","really-simple-ssl") .' '.
			                     sprintf(__('Get more information from the Really Simple SSL dashboard on %s'), $this->domain() ),
			        'url' => 'https://really-simple-ssl.com/manual/vulnerabilities#quarantine',
		        ],
	        ];

	        $mailer->send_mail();
        }
    }
	$vulnerabilities = new rsssl_vulnerabilities_pro();
}
