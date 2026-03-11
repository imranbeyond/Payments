@extends('backend.layouts.app')

@section('title', $gateway['name'] . ' Configuration | ' . config('app.name'))

@push('after-styles')
<style>
/* Gateway brand colors */
.gateway-icon-stripe { color: #635BFF; }
.gateway-icon-paypal { color: #003087; }
.gateway-icon-razorpay { color: #0C2451; }
.gateway-icon-payu { color: #00C853; }
.gateway-icon-telr { color: #E8412F; }
.gateway-icon-myfatoorah { color: #00A650; }
.gateway-icon-tap { color: #2ACE80; }

/* Test connection result */
.test-result {
    display: none;
    margin-top: 10px;
    padding: 10px 15px;
    border-radius: 4px;
    font-size: 14px;
}

.test-result.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.test-result.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-8">
                        <h4 class="card-title mb-0">
                            <i class="{{ $gateway['icon'] }} mr-2 gateway-icon-{{ $slug }}"></i>
                            {{ $gateway['name'] }} Configuration
                        </h4>
                    </div>
                    <div class="col-sm-4 text-right">
                        <a href="/external-apps/payment-gateways/settings" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Gateways
                        </a>
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

                <p class="text-muted">{{ $gateway['description'] }}</p>

                <form action="/external-apps/payment-gateways/configure/{{ $slug }}" method="POST" id="gatewayConfigForm">
                    @csrf

                    {{-- Mode --}}
                    <div class="form-group row">
                        <label for="mode" class="col-md-3 col-form-label">
                            Mode <span class="text-danger">*</span>
                        </label>
                        <div class="col-md-6">
                            <select name="mode" id="mode" class="form-control @error('mode') is-invalid @enderror">
                                <option value="sandbox" {{ ($settings['mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>
                                    Sandbox (Testing)
                                </option>
                                <option value="live" {{ ($settings['mode'] ?? '') === 'live' ? 'selected' : '' }}>
                                    Live (Production)
                                </option>
                            </select>
                            @error('mode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Use Sandbox mode for testing. Switch to Live when ready to accept real payments.</small>
                        </div>
                    </div>

                    {{-- Dynamic fields from gateway registry --}}
                    @foreach($gateway['fields'] as $fieldKey => $field)
                    <div class="form-group row">
                        <label for="{{ $fieldKey }}" class="col-md-3 col-form-label">
                            {{ $field['label'] }}
                            @if(!isset($field['required']) || $field['required'] !== false)
                                <span class="text-danger">*</span>
                            @endif
                        </label>
                        <div class="col-md-6">
                            <input type="{{ $field['type'] ?? 'text' }}"
                                   name="{{ $fieldKey }}"
                                   id="{{ $fieldKey }}"
                                   class="form-control @error($fieldKey) is-invalid @enderror"
                                   value="{{ old($fieldKey, $settings[$fieldKey] ?? '') }}"
                                   placeholder="{{ $field['placeholder'] ?? '' }}">
                            @error($fieldKey)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    @endforeach

                    {{-- Test Connection --}}
                    <div class="form-group row">
                        <div class="col-md-6 offset-md-3">
                            <button type="button" id="testConnectionBtn" class="btn btn-outline-info" data-slug="{{ $slug }}">
                                <i class="fas fa-plug mr-1"></i> Test Connection
                            </button>
                            <div id="testResult" class="test-result"></div>
                        </div>
                    </div>

                    <hr/>

                    {{-- Save & Back Buttons --}}
                    <div class="form-group row">
                        <div class="col-md-6 offset-md-3">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save mr-1"></i> Save Configuration
                            </button>
                            <a href="/external-apps/payment-gateways/settings" class="btn btn-secondary ml-2">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
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
});
</script>
@endpush
