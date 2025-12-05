@extends('layouts.vendor.app')

@section('title', translate('messages.add_new_unit'))

@section('content')
<div class="content container-fluid">
    <div class="card">
        <div class="card-header border-0 align-items-start flex-wrap">
            <h4 class="mb-0">{{ translate('messages.add_new_unit') }}</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('vendor.unit.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="input-label" for="unit">{{ translate('messages.unit_name') }} <span class="text-danger">*</span></label>
                    <input type="text" id="unit" name="unit" class="form-control" required maxlength="191">
                </div>
                <div class="btn--container justify-content-end">
                    <button type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                    <button type="submit" class="btn btn--primary">{{translate('messages.submit')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
