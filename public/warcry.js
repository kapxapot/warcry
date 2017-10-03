$(function() {
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
	saveToken(data['token']);
	location.reload();
}

function saveToken(token) {
	localStorage.setItem('auth_token', token);
}

function loadToken() {
	return localStorage.getItem('auth_token');
}

function hasToken() {
	return loadToken();
}

function getHeaders() {
	return {
		Authorization: 'Basic ' + loadToken(),
	};
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

function parseDate(input) {
	var mainParts = input.split(' ');
	var datePart = mainParts[0];
	var timePart = mainParts[1];

	var dateSubparts = datePart.split('-');
	var timeSubparts = timePart.split(':');

	return new Date(dateSubparts[0], dateSubparts[1] - 1, dateSubparts[2], timeSubparts[0], timeSubparts[1], timeSubparts[2]);
}

function dateToString(date) {
	return date.getFullYear() + '-' + ('0' + (date.getMonth() + 1)).slice(-2) + '-' + ('0' + date.getDate()).slice(-2);
}
