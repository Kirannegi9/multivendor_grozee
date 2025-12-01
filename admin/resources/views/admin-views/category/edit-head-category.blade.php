@extends('layouts.admin.app')

@section('title', translate('messages.Edit Head Category'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span>{{ translate('edit_head_category') }}</span>
            </h1>
        </div>
        <!-- End Page Header -->

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.head-category.update', $category->id) }}" method="POST">
                    @csrf
                    @method('POST') {{-- Use POST as per your routes, but typically PUT is used for updates --}}
                    
                    <div class="form-group">
                        <label class="input-label">{{ translate('messages.name') }}</label>
                        <input type="text" name="name" class="form-control" placeholder="{{ translate('messages.category_name') }}" value="{{ old('name', $category->name) }}" maxlength="191" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">{{ translate('messages.update') }}</button>
                    <a href="{{ route('admin.head-category.indexx') }}" class="btn btn-secondary">{{ translate('messages.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
@endsection
