<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Mastodon Twitter</title>
</head>
<body>
<!-- original author: https://github.com/AleksandrHovhannisyan -->
<div class="dark">
    <div class="min-h-screen dark:bg-neutral-900">
      <header class="fixed inset-x-0 bottom-0 bg-neutral-800 sm:relative">
        <div class="mx-auto justify-between p-3 sm:flex sm:max-w-4xl sm:p-4">
          <!-- title -->
    
          <a href="/" class="hidden items-center gap-1 sm:flex">
            <span class="font-fira text-lg font-bold text-white">Mastodon Twitter</span>
          </a>
  
          <!-- navigation right -->
          <div class="flex sm:gap-2">
            <a href="/" class="block flex-1 py-2 text-center text-lg text-white hover:bg-neutral-700 sm:px-3">Blog</a>
          </div>
        </div>
      </header>
      <main class="mx-auto max-w-3xl p-4 selection:bg-black selection:text-white">
        @yield('content') 
      </main>
      <!-- Footer -->
      <section class="font-fira mt-10 bg-neutral-800 p-8 pb-20 sm:pb-8">
        <div class="mx-auto flex flex-col sm:max-w-4xl sm:flex-row sm:justify-between">
          <div class="flex flex-col items-center sm:items-start">
            <h3 class="text-2xl font-bold text-white">Thanks for reading!</h3>
            <p class="mt-2 text-neutral-200">© Aleksandr Hovhannisyan, 2019–Present</p>
            <p class="mt-2 text-sm text-neutral-200">Last built on Tuesday, October 4, 2022 at 10:28 PM UTC</p>
          </div>
  
          <!-- links -->
          <div class="mt-8 flex justify-center gap-4">
            <a href="#" class="text-white hover:underline sm:text-lg">Twitter</a>
            <a href="#" class="text-white hover:underline sm:text-lg">GitHub</a>
            <a href="#" class="text-white hover:underline sm:text-lg">LinkedIn</a>
            <a href="#" class="text-white hover:underline sm:text-lg">Sitemap</a>
            <a href="#" class="text-white hover:underline sm:text-lg">RSS</a>
          </div>
        </div>
      </section>
    </div>
  </div>
  
</body>
</html>