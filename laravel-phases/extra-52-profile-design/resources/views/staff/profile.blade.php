@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Staff Profile')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
  @include('partials.profile-view', ['area' => 'staff', 'user' => $user])
@endsection
