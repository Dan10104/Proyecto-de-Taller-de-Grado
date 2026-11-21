<script setup>
defineProps({
  name: String
});
const paths = {
  chart:'M4 4v16h16M8 16v-5M13 16V6M18 16v-8',
  calendar:'M5 5h14v15H5ZM8 3v4M16 3v4M5 10h14M8 14h2M14 14h2',
  monitor:'M3 4h18v12H3ZM8 21h8M12 16v5',
  exchange:'M4 7h15l-4-4M20 17H5l4 4M19 7l-4 4M5 17l4-4',
  tool:'m14 6 4 4M4 20l7-7M14 3l-3 3 1 6 6 1 3-3-5 1-3-3Z',
  qr:'M3 3h6v6H3ZM15 3h6v6h-6ZM3 15h6v6H3ZM15 15h3v3h3v3h-6ZM12 3v6M3 12h6M12 12h3M12 18v3M18 12h3',
  sensor:'M7 7h10v10H7ZM10 10h4v4h-4ZM9 3v4M15 3v4M9 17v4M15 17v4M3 9h4M3 15h4M17 9h4M17 15h4',
  signal:'M4 8a12 12 0 0 1 16 0M7 11a8 8 0 0 1 10 0M10 14a3 3 0 0 1 4 0M12 18h.01',
  clock:'M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0ZM12 6v6l4 2',
  checklist:'M9 5h11M9 12h11M9 19h11M3 5l1 1 2-3M3 12l1 1 2-3M3 19l1 1 2-3',
  network:'M9 3h6v6H9ZM2 15h6v6H2ZM16 15h6v6h-6ZM12 9v3M5 15v-3h14v3',
  report:'M5 3h9l5 5v13H5ZM14 3v5h5M8 12h8M8 16h5',
  activity:'M3 3h18v18H3ZM3 12h4l3-5 4 10 3-5h4',
  chat:'M3 4h18v13H9l-6 4ZM7 9h10M7 13h6',
  check:'m5 12 4 4L19 6',
  close:'m6 6 12 12M6 18 18 6',
  bell:'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4',
  home:'M3 10 12 3l9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z',
  users:'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M16 3a4 4 0 0 1 0 8M22 21v-2a4 4 0 0 0-3-3.87M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
  shield:'M12 3 3 7v6c0 5 9 9 9 9s9-4 9-9V7ZM8 12l3 3 5-6',
  logout:'M9 21H4V3h5M14 8l5 4-5 4M8 12h11',
  arrow:'M5 12h14M13 6l6 6-6 6',
  plus:'M12 5v14M5 12h14',
  menu:'M4 6h16M4 12h16M4 18h16',
  search:'m21 21-5-5M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z',
  chevron:'m6 9 6 6 6-6',
  info:'M12 11v5M12 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
  alert:'M12 9v4M12 17h.01M10.3 3.9 2.5 17.5a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
  checkCircle:'m8.5 12 2.5 2.5 4.5-5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
  xCircle:'m9 9 6 6M15 9l-6 6M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
  filter:'M4 5h16l-6 7v6l-4 2v-8Z',
  eye:'M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
  lock:'M6 11h12v9H6ZM8 11V8a4 4 0 0 1 8 0v3',
  edit:'M4 20h4L19 9l-4-4L4 16ZM13.5 6.5l4 4',
  download:'M12 4v11M7 11l5 5 5-5M5 20h14',
  copy:'M9 9h10v11H9ZM5 15V4h10',
  layers:'m12 3 9 5-9 5-9-5ZM3 12l9 5 9-5M3 16l9 5 9-5',
  cube:'m12 3 8 4.5v9L12 21l-8-4.5v-9ZM12 12l8-4.5M12 12 4 7.5M12 12v9',
  hash:'M9 4 7 20M17 4l-2 16M4 9h16M3 15h16',
  pin:'M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11ZM14.5 10a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z',
  user:'M19 21v-1a5 5 0 0 0-5-5h-4a5 5 0 0 0-5 5v1M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
  key:'M14 10a4 4 0 1 0-3.9 4L4 20v-3h3v-3h3l1.2-1.2M16.5 8.5h.01',
  refresh:'M20 11a8 8 0 0 0-14.5-4.5L4 8M4 4v4h4M4 13a8 8 0 0 0 14.5 4.5L20 16M20 20v-4h-4',
  print:'M7 8V3h10v5M7 17H4v-7h16v7h-3M7 14h10v7H7Z',
  projector:'M3 9h18v8H3ZM7 17v2M17 17v2M19 13a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6 12h3',
  laptop:'M5 5h14v10H5ZM2 19h20M9 19l.5-4M15 19l-.5-4',
  cpu:'M7 7h10v10H7ZM10 10h4v4h-4ZM9 3v4M15 3v4M9 17v4M15 17v4M3 9h4M3 15h4M17 9h4M17 15h4',
  door:'M6 21V4h10v17M4 21h16M13 12h.01',
  building:'M3 21h18M6 21V8h12v13M3 8l9-5 9 5ZM9 11v6M15 11v6'
};
</script>

<template>
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path :d="paths[name] || paths.shield" />
  </svg>
</template>
