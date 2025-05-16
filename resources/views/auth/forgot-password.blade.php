@extends('layouts.app')

@section('title', 'Şifremi Unuttum')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card bg-dark border-secondary">
                    <div class="card-header border-secondary">
                        <h4 class="mb-0">Şifremi Unuttum</h4>
                    </div>
                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta Adresi</label>
                                <input id="email" type="email" class="form-control bg-dark text-light border-secondary @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Şifre Sıfırlama Bağlantısı Gönder
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p><a href="{{ route('login') }}">Giriş sayfasına dön</a></p>
                </div>
            </div>
        </div>
    </div>
@endsection
