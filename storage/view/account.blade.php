@extends('layout')
@section('content')
    <div
        class="max-w-2xl mx-4 sm:max-w-sm md:max-w-sm lg:max-w-sm xl:max-w-sm sm:mx-auto md:mx-auto lg:mx-auto xl:mx-auto mt-16 bg-white shadow-xl rounded-lg text-gray-900">
        <div class="rounded-t-lg h-32 overflow-hidden bg-gray-300">
            <img class="object-cover object-top w-full"
                src='{{ $account->profile_image }}'
                >
        </div>
        <div class="mx-auto w-32 h-32 relative -mt-16 border-4 border-white rounded-full overflow-hidden">
            <img class="object-cover object-center h-32"
                src='{{ $account->avatar? :'https://activitypub.good.news/assets/default-avatar-7e5b201b.jpg' }}'>
        </div>
        <div class="text-center mt-2">
            <h2 class="font-semibold">{{ $account->display_name}} </h2>
            <p class="text-gray-500">{{ $account->note }}</p>
        </div>
        <ul class="py-4 mt-2 text-gray-700 flex items-center justify-around">
            <li class="flex flex-col items-center justify-between">
                <div>关注者</div>
                <div>{{ $account->followers_count }}</div>
            </li>
            <li class="flex flex-col items-center justify-between">
                <div>正在关注</div> 
                <div>{{ $account->following_count }}</div>
            </li>
        </ul>
    </div>
    @foreach ($statuses as $status)
        @include('common.tweet', ['status' => $status])
    @endforeach

@endsection
