@extends('backend.layouts.app')

@section('title', 'Payment Gateway Configuration | ' . config('app.name'))

@push('after-styles')
<style>
/* Gateway table */
.gateway-table td {
    vertical-align: middle;
}

.gateway-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.gateway-icon {
    font-size: 28px;
    width: 40px;
    text-align: center;
}

/* Gateway brand colors */
.gateway-icon-stripe { color: #635BFF; }
.gateway-icon-paypal { color: #003087; }
.gateway-icon-razorpay { color: #0C2451; }
.gateway-icon-payu { color: #00C853; }
.gateway-icon-telr { color: #E8412F; }
.gateway-icon-myfatoorah { color: #00A650; }
.gateway-icon-tap { color: #2ACE80; }

/* Status badges */
.badge-status {
    font-size: 12px;
    padding: 5px 12px;
    border-radius: 12px;
    font-weight: 500;
}

.badge-enabled {
    background-color: #d4edda;
    color: #155724;
}

.badge-disabled {
    background-color: #e2e3e5;
    color: #6c757d;
}

/* Mode badges */
.badge-mode {
    font-size: 12px;
    padding: 5px 12px;
    border-radius: 12px;
    font-weight: 500;
}

.badge-sandbox {
    background-color: #fff3cd;
    color: #856404;
}

.badge-live {
    background-color: #d4edda;
    color: #155724;
}

/* Toggle button transition */
.toggle-gateway-btn {
    min-width: 100px;
}
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-5">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-credit-card mr-2"></i>
                            Payment Gateway Configuration
                        </h4>
                    </div>
                </div>
                <hr/>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <p class="text-muted">Manage your payment gateways. Enable, disable, and configure credentials for each gateway.</p>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered gateway-table">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 30%;">Gateway</th>
                                <th style="width: 12%;" class="text-center">Status</th>
                                <th style="width: 12%;" class="text-center">Mode</th>
                                <th style="width: 15%;" class="text-center">Credentials</th>
                                <th style="width: 31%;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gateways as $slug => $gateway)
                            <tr id="gateway-row-{{ $slug }}">
                                <td>
                                    <div class="gateway-info">
                                        <i class="{{ $gateway['icon'] }} gateway-icon gateway-icon-{{ $slug }}"></i>
                                        <div>
                                            <strong>{{ $gateway['name'] }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $gateway['description'] }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge badge-status {{ $gateway['enabled'] ? 'badge-enabled' : 'badge-disabled' }}" id="status-badge-{{ $slug }}">
                                        {{ $gateway['enabled'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge badge-mode {{ $gateway['mode'] === 'live' ? 'badge-live' : 'badge-sandbox' }}" id="mode-badge-{{ $slug }}">
                                        {{ ucfirst($gateway['mode']) }}
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    @if($gateway['has_credentials'])
                                        <span class="text-success"><i class="fas fa-check-circle"></i> Configured</span>
                                    @else
                                        <span class="text-muted"><i class="fas fa-times-circle"></i> Not set</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <a href="/external-apps/payment-gateways/configure/{{ $slug }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-cog mr-1"></i> Configure
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm {{ $gateway['enabled'] ? 'btn-outline-danger' : 'btn-outline-success' }} toggle-gateway-btn"
                                            data-slug="{{ $slug }}"
                                            data-enabled="{{ $gateway['enabled'] ? '1' : '0' }}"
                                            id="toggle-btn-{{ $slug }}">
                                        <i class="fas {{ $gateway['enabled'] ? 'fa-toggle-off' : 'fa-toggle-on' }} mr-1"></i>
                                        {{ $gateway['enabled'] ? 'Disable' : 'Enable' }}
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script>
$(document).ready(function() {

    function getCsrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });

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

                    var statusBadge = $('#status-badge-' + slug);
                    statusBadge
                        .text(enabled ? 'Enabled' : 'Disabled')
                        .removeClass('badge-enabled badge-disabled')
                        .addClass(enabled ? 'badge-enabled' : 'badge-disabled');

                    btn.removeClass('btn-outline-success btn-outline-danger')
                       .addClass(enabled ? 'btn-outline-danger' : 'btn-outline-success')
                       .html('<i class="fas ' + (enabled ? 'fa-toggle-off' : 'fa-toggle-on') + ' mr-1"></i> ' + (enabled ? 'Disable' : 'Enable'))
                       .data('enabled', enabled ? '1' : '0');

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
        $('.card-body hr').first().after(alert);
        setTimeout(function() {
            alert.alert('close');
        }, 5000);
    }
});
</script>
@endpush
