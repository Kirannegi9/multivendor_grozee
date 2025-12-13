@extends('layouts.vendor.app')

@section('title', translate('messages.unit_list'))

@section('content')
<div class="content container-fluid">
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 align-items-start flex-wrap">
                    <h4 class="mb-0">{{ translate('messages.add_new_unit') }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('vendor.unit.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label" for="unit">{{ translate('messages.unit_name') }} <span class="text-danger">*</span></label>
                            <input type="text" id="unit" name="unit" class="form-control" required maxlength="191" placeholder="{{ translate('messages.unit_name') }}">
                        </div>
                        <div class="btn--container justify-content-end">
                            <button type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                            <button type="submit" class="btn btn--primary">{{translate('messages.submit')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2 border-0">
                    <div class="search--button-wrapper">
                        <h5 class="card-title">
                            {{translate('messages.unit_list')}}
                            <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($units) }}</span></h5>
                        <form class="search-form d-inline-block" style="width:auto">
                            <div class="input-group input--group">
                                <input id="datatableSearch_" type="search" name="search" class="form-control"
                                       placeholder="{{translate('messages.search_unit')}}" aria-label="Search">
                                <button type="submit" class="btn btn--secondary">
                                    <i class="tio-search"></i>
                                </button>
                            </div>
                        </form>
                        <div class="d-flex align-items-center ml-auto">
                            <div class="hs-unfold mr-2">
                                <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40" href="javascript:;"
                                   data-hs-unfold-options='{
                                       "target": "#unitsExportDropdown",
                                       "type": "css-animation"
                                   }'>
                                    <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                                </a>
                                <div id="unitsExportDropdown"
                                     class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                                    <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                                    <a class="dropdown-item" href="{{ route('vendor.unit.export', ['type' => 'excel']) }}">
                                        <img class="avatar avatar-xss avatar-4by3 mr-2"
                                             src="{{ asset('assets/admin') }}/svg/components/excel.svg"
                                             alt="Image Description">
                                        {{ translate('messages.excel') }}
                                    </a>
                                    <a class="dropdown-item" href="{{ route('vendor.unit.export', ['type' => 'csv']) }}">
                                        <img class="avatar avatar-xss avatar-4by3 mr-2"
                                             src="{{ asset('assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                             alt="Image Description">
                                        .{{ translate('messages.csv') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive datatable-custom">
                    <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                        <tr class="text-center">
                            <th class="border-0">{{translate('sl')}}</th>
                            <th class="border-0">{{translate('messages.unit')}}</th>
                            <th class="border-0">{{translate('messages.action')}}</th>
                        </tr>
                        </thead>
                        <tbody id="set-rows" class="text-center">
                        @forelse($units as $key => $unit)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <span title="{{ $unit->unit }}" class="d-block font-size-sm text-body">
                                        {{ Str::limit($unit->unit, 20, '...') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="btn action-btn btn--primary btn-outline-primary" 
                                           href="{{ route('vendor.unit.edit', $unit->id) }}" 
                                           title="{{ translate('messages.edit') }}">
                                            <i class="tio-edit"></i>
                                        </a>
                                        <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                           href="javascript:;" 
                                           data-id="unit-{{ $unit->id }}" 
                                           data-message="{{ translate('Want to delete this unit ?') }}"
                                           title="{{ translate('messages.delete') }}">
                                            <i class="tio-delete-outlined"></i>
                                        </a>
                                        <form action="{{ route('vendor.unit.delete', $unit->id) }}" method="POST" id="unit-{{ $unit->id }}" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">{{ translate('messages.no_data_found') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    "use strict";
    $(document).on('click', '.form-alert', function(e){
        e.preventDefault();
        let formId = $(this).data('id');
        let message = $(this).data('message');
        Swal.fire({
            title: '{{ translate('Are you sure ?') }}',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '{{ translate('Yes') }}',
            cancelButtonText: '{{ translate('No') }}'
        }).then((result) => {
            if(result.isConfirmed){
                $('#' + formId).submit();
            }
        })
    });
</script>
@endpush
