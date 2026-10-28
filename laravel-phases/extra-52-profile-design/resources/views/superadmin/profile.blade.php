@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Super Admin Profile')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
  @include('partials.profile-view', ['area' => 'superadmin', 'user' => $user])
@endsection
