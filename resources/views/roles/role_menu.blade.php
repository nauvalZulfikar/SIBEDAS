@extends('layouts.vertical', ['subtitle' => 'Role'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Settings', 'subtitle' => 'Role'])

<div class="row d-flex justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-end">
                <a href="{{ route('roles.index', ['menu_id' => $menuId]) }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
            <div class="card-body">
                <h5>Manage Permissions for Role: {{ $role->name }}</h5>
                <form action="{{ route('role-menu.permission.update', ['role_id' => $role->id]) }}?menu_id={{ $menuId }}" method="post">
                    @csrf
                    @method("put")
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Menu</th>
                                    <th>Show</th>
                                    <th>Create</th>
                                    <th>Update</th>
                                    <th>Destroy</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($menus->where('parent_id', null) as $parent)
                                    @php
                                        $role_menu = optional($role_menus->firstWhere('menu_id', $parent->id));
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $parent->name }}</strong></td>
                                        <td>
                                            <input type="checkbox" class="parent-show" name="permissions[{{ $parent->id }}][allow_show]" value="1"
                                                {{ $role_menu && $role_menu->allow_show ? 'checked' : '' }} readonly>
                                        </td>
                                        <td colspan="3"></td>
                                    </tr>

                                    @foreach($menus->where('parent_id', $parent->id) as $child)
                                        @php
                                            $child_role_menu = optional($role_menus->firstWhere('menu_id', $child->id));
                                        @endphp
                                        <tr>
                                            <td>— {{ $child->name }}</td>
                                            <td>
                                                <input type="checkbox" class="child-permission" data-parent-id="{{ $parent->id }}"
                                                    name="permissions[{{ $child->id }}][allow_show]" value="1"
                                                    {{ $child_role_menu && $child_role_menu->allow_show ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <input type="checkbox" class="child-permission" data-parent-id="{{ $parent->id }}"
                                                    name="permissions[{{ $child->id }}][allow_create]" value="1"
                                                    {{ $child_role_menu && $child_role_menu->allow_create ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <input type="checkbox" class="child-permission" data-parent-id="{{ $parent->id }}"
                                                    name="permissions[{{ $child->id }}][allow_update]" value="1"
                                                    {{ $child_role_menu && $child_role_menu->allow_update ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <input type="checkbox" class="child-permission" data-parent-id="{{ $parent->id }}"
                                                    name="permissions[{{ $child->id }}][allow_destroy]" value="1"
                                                    {{ $child_role_menu && $child_role_menu->allow_destroy ? 'checked' : '' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    <button type="submit" class="btn btn-success">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/roles/role_menu.js'])
@endsection