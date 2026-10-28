@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | Change Password')
@section('body_class', 'dashboard-page')
@section('content')
  @include('partials.profile-edit-form', ['area' => 'portal', 'mode' => 'password', 'user' => $user, 'customer' => $customer])
@endsection
