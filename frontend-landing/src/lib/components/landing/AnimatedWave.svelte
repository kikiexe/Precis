<script lang="ts">
  import { onMount } from 'svelte';

  let canvasRef: HTMLCanvasElement;

  onMount(() => {
    if (!canvasRef) return;
    const ctx = canvasRef.getContext('2d');
    if (!ctx) return;

    const chars = '·∘○◯◌●◉';
    let time = 0;
    let animId: number;

    const handleResize = () => {
      if (!canvasRef) return;
      const dpr = window.devicePixelRatio || 1;
      const rect = canvasRef.getBoundingClientRect();
      canvasRef.width = Math.floor(rect.width * dpr);
      canvasRef.height = Math.floor(rect.height * dpr);
    };

    handleResize();
    window.addEventListener('resize', handleResize);

    const render = () => {
      if (!canvasRef) return;
      const dpr = window.devicePixelRatio || 1;
      const width = canvasRef.width / dpr;
      const height = canvasRef.height / dpr;

      ctx.save();
      ctx.scale(dpr, dpr);
      ctx.clearRect(0, 0, width, height);

      ctx.font = '14px monospace';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';

      const cols = Math.max(Math.floor(width / 22), 10);
      const rows = Math.max(Math.floor(height / 22), 5);

      for (let y = 0; y < rows; y++) {
        for (let x = 0; x < cols; x++) {
          const posX = (x + 0.5) * (width / cols);
          const posY = (y + 0.5) * (height / rows);

          const wave1 = Math.sin(x * 0.2 + time * 2) * Math.cos(y * 0.15 + time);
          const wave2 = Math.sin((x + y) * 0.1 + time * 1.5);
          const wave3 = Math.cos(x * 0.1 - y * 0.1 + time * 0.8);
          const combined = (wave1 + wave2 + wave3) / 3;

          const normalized = (combined + 1) / 2;
          const charIndex = Math.floor(normalized * (chars.length - 1));
          const alpha = 0.15 + normalized * 0.5;

          ctx.fillStyle = `rgba(15, 98, 254, ${alpha})`;
          ctx.fillText(chars[charIndex], posX, posY);
        }
      }

      ctx.restore();

      time += 0.03;
      animId = requestAnimationFrame(render);
    };

    render();

    return () => {
      window.removeEventListener('resize', handleResize);
      cancelAnimationFrame(animId);
    };
  });
</script>

<canvas bind:this={canvasRef} class="block size-full"></canvas>
