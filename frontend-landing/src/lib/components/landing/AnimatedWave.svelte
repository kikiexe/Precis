<script lang="ts">
  import { onMount } from "svelte";

  let canvasRef: HTMLCanvasElement;

  onMount(() => {
    if (!canvasRef) return;
    const ctx = canvasRef.getContext("2d");
    if (!ctx) return;

    const chars = "·∘○◯◌●◉";
    let time = 0;
    let animId: number;

    const handleResize = () => {
      const dpr = window.devicePixelRatio || 1;
      const rect = canvasRef.getBoundingClientRect();
      canvasRef.width = rect.width * dpr;
      canvasRef.height = rect.height * dpr;
      ctx.scale(dpr, dpr);
    };

    handleResize();
    window.addEventListener("resize", handleResize);

    const render = () => {
      const rect = canvasRef.getBoundingClientRect();
      ctx.clearRect(0, 0, rect.width, rect.height);

      ctx.font = "14px monospace";
      ctx.textAlign = "center";
      ctx.textBaseline = "middle";

      const cols = Math.floor(rect.width / 20);
      const rows = Math.floor(rect.height / 20);

      for (let y = 0; y < rows; y++) {
        for (let x = 0; x < cols; x++) {
          const posX = (x + 0.5) * (rect.width / cols);
          const posY = (y + 0.5) * (rect.height / rows);

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

      time += 0.03;
      animId = requestAnimationFrame(render);
    };

    render();

    return () => {
      window.removeEventListener("resize", handleResize);
      cancelAnimationFrame(animId);
    };
  });
</script>

<canvas bind:this={canvasRef} class="w-full h-full block"></canvas>
