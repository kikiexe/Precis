<script lang="ts">
  import { onMount } from 'svelte';

  let canvasRef: HTMLCanvasElement;

  onMount(() => {
    if (!canvasRef) return;
    const ctx = canvasRef.getContext('2d');
    if (!ctx) return;

    const chars = '░▒▓█▀▄▌▐│─┤├┴┬╭╮╰╯';
    let angle = 0;
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

      const centerX = width / 2;
      const centerY = height / 2;
      const radius = Math.min(width, height) * 0.4;

      ctx.font = '12px monospace';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';

      const points: Array<{ x: number; y: number; z: number; char: string }> = [];

      for (let theta = 0; theta < Math.PI * 2; theta += 0.15) {
        for (let phi = 0; phi < Math.PI; phi += 0.15) {
          const x = Math.sin(phi) * Math.cos(theta + angle * 0.5);
          const y = Math.sin(phi) * Math.sin(theta + angle * 0.5);
          const z = Math.cos(phi);

          const rotX = angle * 0.3;
          const y1 = y * Math.cos(rotX) - z * Math.sin(rotX);
          const z1 = y * Math.sin(rotX) + z * Math.cos(rotX);

          const rotY = angle * 0.2;
          const x2 = x * Math.cos(rotY) - z1 * Math.sin(rotY);
          const z2 = x * Math.sin(rotY) + z1 * Math.cos(rotY);

          const depth = (z2 + 1) / 2;
          const charIndex = Math.floor(depth * (chars.length - 1));

          points.push({
            x: centerX + x2 * radius,
            y: centerY + y1 * radius,
            z: z2,
            char: chars[charIndex],
          });
        }
      }

      points.sort((a, b) => a.z - b.z);

      points.forEach((p) => {
        const alpha = 0.4 + (p.z + 1) * 0.55;
        ctx.fillStyle = `rgba(15, 98, 254, ${Math.min(1, alpha)})`;
        ctx.fillText(p.char, p.x, p.y);
      });

      ctx.restore();

      angle += 0.02;
      animId = requestAnimationFrame(render);
    };

    render();

    return () => {
      window.removeEventListener('resize', handleResize);
      cancelAnimationFrame(animId);
    };
  });
</script>

<canvas bind:this={canvasRef} class="block h-full w-full"></canvas>
