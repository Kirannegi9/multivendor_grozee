@extends('layouts.admin.app')

@section('title', translate('messages.Add new head category'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span>{{ translate('add_new_head_category') }}</span>
            </h1>
        </div>
        <!-- End Page Header -->

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.head-category.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="input-label">{{ translate('messages.name') }}</label>
                        <input type="text" name="name" class="form-control" placeholder="{{ translate('messages.new_category') }}" value="{{ old('name') }}" maxlength="191" required>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ translate('messages.add') }}</button>
                </form>
            </div>
        </div>

        <!-- Category List -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('messages.category_list') }}</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>S No.</th>
                            <th class="col-8">{{ translate('messages.name') }}</th>
                            <th>{{ translate('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $categories = DB::table('head_category')->get();
                        @endphp
                        @foreach ($categories as $key => $category)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $category->name }}</td>
                                <td>
                                    <a href="{{ route('admin.head-category.edit', $category->id) }}" class="btn btn-warning btn-sm">
                                        <i class="tio-edit"></i> {{ translate('messages.edit') }}
                                    </a>
                                    <form action="{{ route('admin.head-category.delete', $category->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ translate('messages.are_you_sure') }}?')">
                                            <i class="tio-delete"></i> {{ translate('messages.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        @if(count($categories) == 0)
                            <tr>
                                <td colspan="3" class="text-center">{{ translate('messages.no_data_found') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
