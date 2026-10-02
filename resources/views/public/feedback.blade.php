<x-layout title="Feedback" description="Share feedback or suggestions with Bijak Tuition Centre.">

    <section class="page-hero">
        <div class="container">
            <span class="eyebrow">Help us improve</span>
            <h1>Feedback</h1>
            <p>Share your experience, suggestions or concerns with our team.</p>
        </div>
    </section>

    <section class="section">
        <div class="container feedback-layout">
            <div class="form-card">
                <h2>Send us your feedback</h2>
                <p>
                    Your contact details will only be used to respond to your
                    submission and will not be displayed publicly.                
                </p>

                @if (session('success'))
                    <div class="alert alert-success" role="status">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error" role="alert">
                        <strong>Please correct the following:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('public.feedback.store') }}" class="feedback-form">
                    @csrf

                    <div class="form-field">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="255" autocomplete="name" required>

                        @error('name')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>

                        @error('email')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="phone">Phone number<span>(optional)</span></label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" maxlength="30" autocomplete="tel">

                        @error('phone')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="honeypot" aria-hidden="true">
                        <label for="website">Website</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-field">
                        <label for="message">Feedback</label>
                        <textarea id="message" name="message" rows="7" minlength="10" maxlength="2000" required>{{ old('message') }}</textarea>

                        @error('message')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="button button-primary">
                        Submit feedback
                    </button>
                </form>
            </div>

            <aside>
                <div class="section-heading">
                    <span class="eyebrow">Community voices</span>
                    <h2>What people say</h2>
                </div>

                @forelse ($feedbackEntries as $feedback)
                    <blockquote class="feedback-entry reveal">
                        <p>“{{ $feedback->message }}”</p>

                        <footer>
                            <strong>{{ $feedback->name }}</strong>
                            <time datetime="{{ $feedback->created_at->toDateString() }}">
                                {{ $feedback->created_at->format('d M Y') }}
                            </time>
                        </footer>
                    </blockquote>
                @empty
                    <div class="empty-state">
                        <p>No reviewed feedback is available yet.</p>
                    </div>
                @endforelse

                <div class="pagination-wrapper">
                    {{ $feedbackEntries->links() }}
                </div>
            </aside>
        </div>
    </section>
</x-layout>

