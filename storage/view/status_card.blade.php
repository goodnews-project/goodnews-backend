<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {

            color: rgb(44, 62, 80)
        }

        .meida-display {
            flex: 1;
            min-width: calc(50% - 10px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bar-frame {
            width: 100%;
            display: flex;
            margin-top: 10px;
            margin-bottom: 10px;
            justify-content: space-between
        }

        .bar-frame .bar-item {
            cursor: pointer;
            /* height: 34px; */
            display: flex;
            align-items: center;
        }

        .bar-frame .bar-item .icon-btn {
            background-color: #fff;
            width: 34px;
            height: 34px;
            border-radius: 34px;
            background: transparent;
            transition: all 0.3s;
            font-size: 18px;
        }

        .bar-frame .bar-item .icon-btn.disabled {
            cursor: not-allowed;
        }

        .el-icon {
            --color: inherit;
            height: 1em;
            width: 1em;
            line-height: 1em;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            position: relative;
            fill: currentColor;
            font-size: inherit;
        }

        .el-icon svg {
            width: 1em;
            height: 1em;
        }

        .preview-card {
            border: 1px solid rgb(229 231 235 / 1);
            cursor: pointer;
        }

        .preview-card :hover {
            background-color: rgba(0, 0, 0, 0.03);
        }
    </style>
</head>

<body>

    <article class="flex flex-col gap-3 border-b border-light-border w-full outline-none  p-5">
        <div class="flex">
            <div class="mr-4">
                <div><img src="{{ $status['account']['avatar'] }}"
                        class="h-12 w-12 rounded-full flex-none el-tooltip__trigger el-tooltip__trigger"></div>
            </div>
            <div class="flex flex-col userinfo">
                <div>
                    <div class="cursor-pointer el-tooltip__trigger el-tooltip__trigger">
                        <p class="font-bold hover:underline user-name">{{ $status['account']['display_name'] }}</p>
                        <div
                            class="group relative self-start text-light-primary dark:text-dark-primary grid [&amp;>div]:translate-y-7">
                            <p class="truncate text-light-secondary dark:text-dark-secondary" tabindex="-1">
                                {{ $status['account']['acct'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div><!--v-if-->
        <div class="py-2 tweet-html">
            {!! $status['content_rendered'] !!}
        </div>
        @if ($status['attachments'])
            <div class="-m-1 flex flex-wrap md:-m-2">
                @foreach ($status['attachments'] as $attachment)
                    <div class="meida-display flex flex-wrap my-2 rounded-lg overflow-hidden">
                        <div class="w-full p-1 md:p-2"><img alt="gallery" src="{{ $attachment['url'] }}"></div>
                    </div>
                @endforeach
            </div>
        @endif
        @if ($status['previewCard'])
            <div class="preview-card w-full bg-white overflow-hidden border-gray-200 rounded-lg mb-5">
                @if ($status['previewCard']['image_url'])
                    <div>
                        <!-- News image -->
                        <img class="object-cover w-full aspect-[1.91/1]" src="{{ $status['previewCard']['image_url'] }}"
                            alt="" />
                        <!-- News details -->
                        <div class="p-4 pl-2">
                            <h3 class="text-sm mb-2">{{ $status['previewCard']['provider_name'] }}</h3>
                            <!-- News title -->
                            <h2 class="font-bold text-xl mb-2">{{ $status['previewCard']['title'] }}</h2>
                            <!-- News summary -->
                            <p class="text-gray-700">
                                {{ $status['previewCard']['description'] }}
                            </p>
                        </div>
                    </div>
                @else
                    <div class="flex justify-start items-center">
                        <div class="p-4 w-[130px] h-[130px] bg-[#f7f9f9] icon-block">
                            <svg-icon class="icon-box" name="file-lines"></svg-icon>
                        </div>
                        <div class="p-4">
                            <h1 class="text-base font-color-desc">{{ $status['previewCard']['provider_name'] }}</h1>
                            <p class="text-base font-color-title">{{ $status['previewCard']['title'] }}</p>
                            <p class="text-base font-color-desc">{{ $status['previewCard']['description'] }}</p>
                        </div>
                    </div>
                @endif
            </div>
        @endif
        <div class="whitespace-nowrap text-light-secondary dark:text-dark-secondary hover:underline">
            {{ $status['created_at']->diffForHumans() }}</div>


    </article>
    <div class="px-5 border-b">
        <div class="bar-frame">
            <div class="bar-item flex1 text-dark">
                <div class="item-icon"><i class="el-icon icon-btn" style="position: static;">
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">
                            <g>
                                <path
                                    d="M1.751 10c0-4.42 3.584-8 8.005-8h4.366c4.49 0 8.129 3.64 8.129 8.13 0 2.96-1.607 5.68-4.196 7.11l-8.054 4.46v-3.69h-.067c-4.49.1-8.183-3.51-8.183-8.01zm8.005-6c-3.317 0-6.005 2.69-6.005 6 0 3.37 2.77 6.08 6.138 6.01l.351-.01h1.761v2.3l5.087-2.81c1.951-1.08 3.163-3.13 3.163-5.36 0-3.39-2.744-6.13-6.129-6.13H9.756z">
                                </path>
                            </g>
                        </svg>
                    </i></div><span class="item-text">{{ $status->reply_count }}</span>
            </div>
            <div class="bar-item flex1"
                style="--hover-color: rgba(249, 24, 128, 1); --hover-icon-bg-color: rgba(249, 24, 128, 0.1);">
                <div class="item-icon"><i class="el-icon icon-btn" style="position: static;">
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">
                            <g>
                                <path
                                    d="M16.697 5.5c-1.222-.06-2.679.51-3.89 2.16l-.805 1.09-.806-1.09C9.984 6.01 8.526 5.44 7.304 5.5c-1.243.07-2.349.78-2.91 1.91-.552 1.12-.633 2.78.479 4.82 1.074 1.97 3.257 4.27 7.129 6.61 3.87-2.34 6.052-4.64 7.126-6.61 1.111-2.04 1.03-3.7.477-4.82-.561-1.13-1.666-1.84-2.908-1.91zm4.187 7.69c-1.351 2.48-4.001 5.12-8.379 7.67l-.503.3-.504-.3c-4.379-2.55-7.029-5.19-8.382-7.67-1.36-2.5-1.41-4.86-.514-6.67.887-1.79 2.647-2.91 4.601-3.01 1.651-.09 3.368.56 4.798 2.01 1.429-1.45 3.146-2.1 4.796-2.01 1.954.1 3.714 1.22 4.601 3.01.896 1.81.846 4.17-.514 6.67z">
                                </path>
                            </g>
                        </svg>
                    </i></div><span class="item-text">{{ $status->reblog_count }}</span>
            </div>
            <div class="bar-item flex1"
                style="--hover-color: rgb(0, 186, 124); --hover-icon-bg-color: rgba(0, 186, 124, 0.1);">
                <div class="item-icon"><i class="el-icon icon-btn" style="position: static;">
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">
                            <g>
                                <path
                                    d="M4.5 3.88l4.432 4.14-1.364 1.46L5.5 7.55V16c0 1.1.896 2 2 2H13v2H7.5c-2.209 0-4-1.79-4-4V7.55L1.432 9.48.068 8.02 4.5 3.88zM16.5 6H11V4h5.5c2.209 0 4 1.79 4 4v8.45l2.068-1.93 1.364 1.46-4.432 4.14-4.432-4.14 1.364-1.46 2.068 1.93V8c0-1.1-.896-2-2-2z">
                                </path>
                            </g>
                        </svg>
                    </i></div><span class="item-text">{{ $status->fave_count }}</span>
            </div>
        </div>
    </div>

</body>

</html>
