@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Edit Profile')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
  @include('partials.profile-edit-form', ['area' => 'admin', 'mode' => 'profile', 'user' => $user])
@endsection
