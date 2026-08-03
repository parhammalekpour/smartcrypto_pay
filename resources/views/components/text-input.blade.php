@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border border-slate-200 bg-slate-50 text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 rounded-2xl shadow-sm transition duration-150 outline-none']) }}>
