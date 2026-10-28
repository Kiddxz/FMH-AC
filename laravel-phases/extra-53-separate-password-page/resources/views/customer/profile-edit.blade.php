@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | Edit Profile')
@section('body_class', 'dashboard-page')
@section('content')
  @include('partials.profile-edit-form', ['area' => 'portal', 'mode' => 'profile', 'user' => $user, 'customer' => $customer])
@endsection
