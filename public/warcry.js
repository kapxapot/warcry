$(document).ready(function() {
	focusOnModals();
    hideAlerts();

    $("[data-hide]").on("click", function() {
        $(this).closest("." + $(this).attr("data-hide")).hide();
    });
});

function focusOnModals() {
    $('.modal').on('shown.bs.modal', function() {
        $('[data-modalfocus]', this).focus();
    });
}

function showAlert(selector, delay = null) {
	$(selector).show();
	
	if (delay) {
		$(selector).fadeTo(delay, 500).slideUp(500, function() {
	        $(this).slideUp(500);
	    });
	}
}

function showAlertSuccess() {
	showAlert('.alert-success', 2000);
}

function showAlertError() {
	showAlert('.alert-danger');
}

function hideAlert(selector) {
    $(selector).hide();
}

function hideAlertSuccess() {
    hideAlert('.alert-success');
}

function hideAlertError() {
	hideAlert('.alert-danger');
}

function hideAlerts() {
    hideAlertSuccess();
    hideAlertError();
}

function showModal(name) {
	hideAlerts();
	$('#' + name + 'View').modal('show');
}

function hideModal(name) {
	$('#' + name + 'View').modal('hide');
}

function signedIn(data) {
	localStorage.setItem('auth_token', data['token']);
	location.reload();
}

function getToken() {
	return localStorage.getItem('auth_token');
}

function parseText(text, format = 'plain') {
	return (format == 'markdown') ? parseMarkdown(text) : text;
}

function parseMarkdown(text) {
	var reader = new commonmark.Parser();
	var writer = new commonmark.HtmlRenderer();
	var parsed = reader.parse(text);
	var result = writer.render(parsed);
	
	return result;
}

function getById(items, id) {
	if (items) {
		var results = $.grep(items, function(e) {
			return e.id === id;
		});
	
		if (results.length > 0) {
			return results[0];
		}
	}
	
	return null;
}
