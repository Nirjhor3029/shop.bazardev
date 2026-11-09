<div class="mt-6 pt-6 border-t" id="sf-qna">
    <h2 class="font-semibold mb-3">{{ __('store.qna') }}</h2>
    @forelse ($answeredQuestions as $q)
        <div class="py-2 border-b border-gray-100 last:border-0">
            <p class="text-sm font-medium">{{ __('store.q') }}: {{ $q->question }}</p>
            <p class="text-sm text-gray-700 mt-0.5">{{ __('store.a') }}: {{ $q->answer }}</p>
        </div>
    @empty
        <p class="text-sm text-gray-500 mb-2">{{ __('store.no_questions_yet') }}</p>
    @endforelse
    @if (session('status'))<p class="text-sm text-green-700 mt-2">{{ session('status') }}</p>@endif
    <form method="POST" action="{{ $store->storePath('/p/'.$product->slug.'/question') }}" class="mt-3 space-y-2 text-sm">
        @csrf
        <p class="font-medium">{{ __('store.ask_question') }}</p>
        <input name="name" placeholder="{{ __('store.name') }}" required maxlength="100" class="border-gray-300 rounded-md w-full max-w-xs">
        <textarea name="question" placeholder="{{ __('store.question_ph') }}" required maxlength="500" rows="2" class="border-gray-300 rounded-md w-full"></textarea>
        <button class="bg-gray-900 text-white text-sm px-4 py-1.5 rounded-md">{{ __('store.submit_question') }}</button>
    </form>
</div>
