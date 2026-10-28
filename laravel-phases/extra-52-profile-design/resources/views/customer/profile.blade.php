@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | Profile')
@section('body_class', 'dashboard-page')
@section('content')
  @include('partials.profile-view', ['area' => 'portal', 'user' => $user, 'customer' => $customer])
@endsection
