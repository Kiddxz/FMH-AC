@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Patient Flow')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
  @include('partials.patient-flow.page')
@endsection
