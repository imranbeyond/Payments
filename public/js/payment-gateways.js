$(document).ready(function() {

    // Get CSRF token from meta tag or hidden form input
    function getCsrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
    }

    // Setup AJAX defaults for all requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });

    // Toggle gateway enable/disable via AJAX
    $(document).on('click', '.toggle-gateway-btn', function() {
        var btn = $(this);
        var slug = btn.data('slug');
        var originalHtml = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Working...');

        $.ajax({
            url: '/external-apps/payment-gateways/toggle/' + slug,
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var enabled = response.enabled;

                    // Update status badge
                    var statusBadge = $('#status-badge-' + slug);
                    statusBadge
                        .text(enabled ? 'Enabled' : 'Disabled')
                        .removeClass('badge-enabled badge-disabled')
                        .addClass(enabled ? 'badge-enabled' : 'badge-disabled');

                    // Update toggle button
                    btn.removeClass('btn-outline-success btn-outline-danger')
                       .addClass(enabled ? 'btn-outline-danger' : 'btn-outline-success')
                       .html('<i class="fas ' + (enabled ? 'fa-toggle-off' : 'fa-toggle-on') + ' mr-1"></i> ' + (enabled ? 'Disable' : 'Enable'))
                       .data('enabled', enabled ? '1' : '0');

                    // Show success notification
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message || 'An error occurred.', 'error');
                    btn.html(originalHtml);
                }
            },
            error: function(xhr) {
                var message = 'An error occurred.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showNotification(message, 'error');
                btn.html(originalHtml);
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Test Connection on gateway config page
    $('#testConnectionBtn').on('click', function() {
        var btn = $(this);
        var slug = btn.data('slug');
        var resultDiv = $('#testResult');
        var originalText = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Testing...');
        resultDiv.hide().removeClass('success error');

        $.ajax({
            url: '/external-apps/payment-gateways/test/' + slug,
            method: 'POST',
            dataType: 'json',
            data: {
                mode: $('#mode').val(),
                api_key: $('#api_key').val(),
                secret_key: $('#secret_key').val()
            },
            success: function(response) {
                resultDiv
                    .addClass(response.success ? 'success' : 'error')
                    .text(response.message)
                    .show();
            },
            error: function(xhr) {
                var message = 'An error occurred while testing the connection.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                resultDiv.addClass('error').text(message).show();
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Simple notification helper
    function showNotification(message, type) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var alert = $(
            '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                message +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span>' +
                '</button>' +
            '</div>'
        );

        // Insert after the card header hr
        $('.card-body hr').first().after(alert);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            alert.alert('close');
        }, 5000);
    }
});
