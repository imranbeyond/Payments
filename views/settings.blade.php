@extends('backend.layouts.app')

@section('title', 'Payment Gateway Configuration | ' . config('app.name'))

@push('after-styles')
<link rel="stylesheet" href="{{ url('modules/payment-gateways/public/css/payment-gateways.css') }}">
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
<script src="{{ url('modules/payment-gateways/public/js/payment-gateways.js') }}"></script>
@endpush
