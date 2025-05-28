<?php
namespace RSSSL\Security\WordPress\Two_Fa\Providers;

class Rsssl_Provider_Loader_Free extends Rsssl_Provider_Loader
{
	public static function get_providers(): array {
		return parent::get_providers();
	}
}