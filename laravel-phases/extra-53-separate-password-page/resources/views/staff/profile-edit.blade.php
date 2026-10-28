@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Edit Profile')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
  @include('partials.profile-edit-form', ['area' => 'staff', 'mode' => 'profile', 'user' => $user])
@endsection
