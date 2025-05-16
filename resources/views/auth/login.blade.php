@extends('layouts.app')

@section('title', 'Giriş')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card bg-dark border-secondary">
                    <div class="card-header border-secondary">
                        <h4 class="mb-0">Giriş</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('login') }}">
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

                            <div class="mb-3">
                                <label for="password" class="form-label">Şifre</label>
                                <input id="password" type="password" class="form-control bg-dark text-light border-secondary @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                                @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <div class="mb-3 form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">
                                    Beni Hatırla
                                </label>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Giriş Yap
                                </button>
                            </div>

                            <div class="mt-3 text-center">
                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        Şifremi Unuttum
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p>Hesabınız yok mu? <a href="{{ route('register') }}">Kayıt Ol</a></p>
                </div>
            </div>
        </div>
    </div>
@endsection
