const rsp_steps = rsp_upgrade.steps;
let rsp_download_link = '';
let rsp_progress = 0;

//set up steps html
let rsp_template = document.getElementById('rsp-step-template').innerHTML;
let rsp_total_step_html = '';
rsp_steps.forEach( (step, i) =>	{
	let stepHtml = rsp_template;
	stepHtml = stepHtml.replace('{doing}', step.doing);
	stepHtml = stepHtml.replace('{step}', 'rsp-step-'+i);
	rsp_total_step_html += stepHtml;
});
document.querySelector('.rsp-install-steps').innerHTML = rsp_total_step_html;

const rsp_set_progress = () => {
	if ( rsp_progress>=100 ) rsp_progress=100;
	let progress_bar_container = document.querySelector(".rsp-progress-bar-container");
	let progressEl = progress_bar_container.querySelector(".rsp-progress");
	let bar = progressEl.querySelector(".rsp-bar");
	bar.style = "width: " + rsp_progress + "%;";

	if ( rsp_progress == 100 ) {
		clearInterval(window.rsp_interval);
	}
}

const rsp_stop_progress = () => {
	clearInterval(window.rsp_interval);
	let progress_bar_container = document.querySelector(".rsp-progress-bar-container");

	let progressEl = progress_bar_container.querySelector(".rsp-progress");
	var bar = progressEl.querySelector(".rsp-bar");
	bar.style = "width: 100%;";
	bar.classList.remove('rsp-green');
	bar.classList.add('rsp-red');
	clearInterval(window.rsp_interval);
}


const rsp_process_step = (current_step) => {
	let previous_progress = current_step * Math.ceil(100/(rsp_upgrade.steps.length));
	let progress_step = (current_step+1) * Math.ceil(100/(rsp_upgrade.steps.length));

	clearInterval(window.rsp_interval);
	window.rsp_interval = setInterval(function () {
		let inc = 0.5;
		//very slow if we're close to the target progress for this step.
		if ( ( rsp_progress > progress_step-1 ) ) {
			inc = 0.01;
		}

		rsp_progress += inc;
		if (rsp_progress >= 100) {
			rsp_progress = 100;
		}
		rsp_set_progress();
	}, 100);

	current_step = parseInt(current_step);
	let step = rsp_steps[current_step];
	let error = step['error'];
	let success = step['success'];

	// Get arguments from url
	const query_string = window.location.search;
	const urlParams = new URLSearchParams(query_string);

	let data = {
		'action': step['action'],
		'token': rsp_upgrade.token,
		'plugin': urlParams.get('plugin'),
		'license': urlParams.get('license'),
		'item_id': urlParams.get('item_id'),
		'api_url': urlParams.get('api_url'),
		'download_link': rsp_download_link,
		'install_pro': true,
	};

	rsp_ajax.get(rsp_upgrade.admin_url, data, function(response) {
		let step_element = document.querySelector(".rsp-step-"+current_step);
		if ( !step_element ) return;

		let step_color = step_element.querySelector(".rsp-step-color");
		let step_text = step_element.querySelector(".rsp-step-text");
		let data = JSON.parse(response);

		if ( data.success ) {
			if ( data.download_link ){
				rsp_download_link = data.download_link;
			}
			step_color.innerHTML = "<div class='rsp-green rsp-bullet'></div>";
			step_text.innerHTML = "<span>"+step.success+"</span>";

			if ( current_step + 1 == rsp_steps.length ) {
				let templateHtml = document.getElementById('rsp-plugin-suggestion-template').innerHTML;
				document.querySelector('.rsp-install-steps').innerHTML = templateHtml;
				document.querySelector('.rsp-install-plugin-modal h3').innerText = rsp_upgrade.finished_title;
				document.querySelector(".rsp-btn.rsp-visit-dashboard").classList.remove("rsp-hidden");
				rsp_progress = 100;
				rsp_set_progress();
			} else {
				rsp_progress = progress_step;
				rsp_set_progress(progress_step);
				rsp_process_step( current_step+1 );
			}
		} else {
			step_color.innerHTML = "<div class='rsp-red rsp-bullet'></div>";
			if ( data.message ) {
				document.querySelector(".rsp-error-message.rsp-"+step['type']+" span").innerText = data.message;
			}
			step_text.innerHTML = "<span>"+step.error+"</span>";
			rsp_stop_progress();
			document.querySelector(".rsp-btn.rsp-cancel").classList.remove("rsp-hidden");
			document.querySelector(".rsp-error-message.rsp-"+step['type']).classList.remove("rsp-hidden");
		}
	});
}
rsp_process_step(0);