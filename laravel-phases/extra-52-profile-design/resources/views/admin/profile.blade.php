@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Admin Profile')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
  @include('partials.profile-view', ['area' => 'admin', 'user' => $user])
@endsection
