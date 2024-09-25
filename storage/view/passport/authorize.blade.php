<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }} - 授权</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Styles -->

    <style>
    </style>
</head>

<body class="center block flex  h-screen dark:bg-black">
    <div class="w-[22rem]  m-auto  align-middle  min-h-60 flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-700 dark:shadow-slate-700/[.7]">
        <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
          <h3 class="text-lg font-bold text-gray-800 text-center dark:text-white">
            <strong>{{ $client->name }}</strong> 向你申请权限
          </h3>
          <p class="mt-2 text-gray-500 text-center dark:text-gray-400"><strong>此应用将会被赋予:</strong></p>
          <ul role="list" class="marker:text-blue-600 pt-2 text-left list-disc ps-5 space-y-2 text-sm text-gray-600 dark:text-gray-400">
            @foreach ($scopes as $scope)
            <li>
                {{ $scope->description }}
            </li>
            @endforeach
          </ul>
          <div class="flex pt-6 flex-wrap gap-2">
            <form method="post" action="/oauth/authorize/approve">
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                授权
              </button>
            </form>
            <form method="post" action="/oauth/authorize/deny">
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <input type="hidden" name="token" value="{{ $token }}">
              <button type="submit" class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-white text-gray-800 hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                取消
              </button>
            </form>
          </div>
          
        </div>
      </div>
    

</body>

</html>
