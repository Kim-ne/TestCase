<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Test Case Generator</title>

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
            rel="stylesheet"
            integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
            crossorigin="anonymous"
        >
    </head>
    <body class="bg-light">
        <main class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="mb-4 text-center">
                        <h1 class="h2 mb-2">Test Case Generator</h1>
                        <p class="mb-0 text-body-secondary">
                            Nhập yêu cầu hoặc tải tài liệu để tạo test case.
                        </p>
                    </div>

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <form action="{{ route('test-case-input') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label" for="text">Nội dung yêu cầu</label>
                                    <textarea
                                        class="form-control @error('text') is-invalid @enderror"
                                        id="text"
                                        name="text"
                                        rows="7"
                                        placeholder="Ví dụ: Người dùng đăng nhập bằng email và mật khẩu hợp lệ..."
                                    >{{ old('text') }}</textarea>
                                    <div class="form-text">Nhập nội dung hoặc tải một tệp bên dưới.</div>
                                    @error('text')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="file">Tài liệu yêu cầu</label>
                                    <input
                                        class="form-control @error('file') is-invalid @enderror"
                                        id="file"
                                        name="file"
                                        type="file"
                                        accept=".pdf,.doc,.docx,.txt"
                                    >
                                    <div class="form-text">Hỗ trợ PDF, DOC, DOCX, TXT; dung lượng tối đa 5 MB.</div>
                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="output_language">Ngôn ngữ kết quả</label>
                                    <select
                                        class="form-select @error('output_language') is-invalid @enderror"
                                        id="output_language"
                                        name="output_language"
                                        required
                                    >
                                        <option value="en" @selected(old('output_language', 'en') === 'en')>English</option>
                                        <option value="vi" @selected(old('output_language') === 'vi')>Tiếng Việt</option>
                                    </select>
                                    @error('output_language')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button class="btn btn-primary w-100" type="submit">
                                    Tạo test case
                                </button>
                            </form>
                        </div>
                    </div>

                    @if (session()->has('test_cases'))
                        <section aria-labelledby="generated-test-cases-title">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h2 class="h4 mb-0" id="generated-test-cases-title">Test case đã tạo</h2>
                                <span class="badge text-bg-secondary">{{ count(session('test_cases')) }} kết quả</span>
                            </div>

                            @forelse (session('test_cases') as $index => $testCase)
                                @php
                                    $priorityClass = match ($testCase['priority'] ?? '') {
                                        'High' => 'text-bg-danger',
                                        'Medium' => 'text-bg-warning',
                                        'Low' => 'text-bg-success',
                                        default => 'text-bg-secondary',
                                    };
                                @endphp

                                <article class="card border-0 shadow-sm mb-3">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                            <h3 class="h5 mb-0">
                                                {{ $index + 1 }}. {{ $testCase['title'] ?? 'Untitled test case' }}
                                            </h3>
                                            <span class="badge {{ $priorityClass }}">
                                                {{ $testCase['priority'] ?? 'Unknown' }}
                                            </span>
                                        </div>

                                        @if (! empty($testCase['preconditions']))
                                            <div class="mb-3">
                                                <h4 class="h6">Điều kiện tiên quyết</h4>
                                                <p class="mb-0">{{ $testCase['preconditions'] }}</p>
                                            </div>
                                        @endif

                                        <div class="mb-3">
                                            <h4 class="h6">Các bước thực hiện</h4>
                                            <ol class="mb-0 ps-3">
                                                @forelse ($testCase['steps'] ?? [] as $step)
                                                    <li>{{ $step }}</li>
                                                @empty
                                                    <li>Không có bước thực hiện.</li>
                                                @endforelse
                                            </ol>
                                        </div>

                                        <div>
                                            <h4 class="h6">Kết quả mong đợi</h4>
                                            <p class="mb-0">{{ $testCase['expected_result'] ?? 'Chưa có kết quả mong đợi.' }}</p>
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="alert alert-info mb-0" role="alert">
                                    Chưa tạo được test case từ nội dung đã cung cấp.
                                </div>
                            @endforelse
                        </section>
                    @endif
                </div>
            </div>
        </main>

        <script
            src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
            crossorigin="anonymous"
        ></script>
    </body>
</html>
