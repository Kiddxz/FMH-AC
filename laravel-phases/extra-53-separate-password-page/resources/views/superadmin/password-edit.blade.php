@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Change Password')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
  @include('partials.profile-edit-form', ['area' => 'superadmin', 'mode' => 'password', 'user' => $user])
@endsection
