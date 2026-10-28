@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Change Password')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
  @include('partials.profile-edit-form', ['area' => 'admin', 'mode' => 'password', 'user' => $user])
@endsection
