<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import type { TimeframeSalesPoint, TimeframePeriod } from '../../../types/app';
  import { formatRupiah } from '@precis/shared-utils';
  import { ZoomIn, ZoomOut, RotateCcw } from 'lucide-svelte';

  interface Props {
    breakdown: TimeframeSalesPoint[];
    totalRevenue: number;
    totalDiscount?: number;
    totalOrders: number;
    averageOrderValue: number;
    growthPercent: number;
    growthLabel: string;
    periodLabel: string;
    selectedTimeframe: TimeframePeriod;
    onSelectTimeframe: (tf: TimeframePeriod) => void;
    isLoading?: boolean;
  }

  let {
    breakdown = [],
    totalRevenue = 0,
    totalDiscount = 0,
    totalOrders = 0,
    averageOrderValue = 0,
    growthPercent = 0,
    growthLabel = '',
    periodLabel = '',
    selectedTimeframe = 'month',
    onSelectTimeframe,
    isLoading = false,
  }: Props = $props();

  let canvasEl = $state<HTMLCanvasElement | null>(null);
  let containerEl = $state<HTMLDivElement | null>(null);

  // --- TradingView Interactive Scale & Viewport State ---
  let zoomX = $state<number>(1.0); // Horizontal time stretch (1.0x - 15.0x) - Lebarkan Sumbu X
  let zoomY = $state<number>(1.0); // Vertical price stretch (0.4x - 6.0x) - Tinggikan Sumbu Y
  let panX = $state<number>(0); // Horizontal pixel pan offset
  let panY = $state<number>(0); // Vertical pixel pan offset

  // Dragging interaction states
  type DragMode = 'none' | 'canvas' | 'axis-x' | 'axis-y';
  let activeDragMode = $state<DragMode>('none');
  let dragStartPos = { x: 0, y: 0 };
  let dragStartZoom = { x: 1.0, y: 1.0 };
  let dragStartPan = { x: 0, y: 0 };

  // Hover & Crosshair states
  let isHoveringYAxis = $state<boolean>(false);
  let isHoveringXAxis = $state<boolean>(false);
  let mousePos = $state<{ x: number; y: number } | null>(null);
  let activeIndex = $state<number | null>(null);

  // Dynamic Momentum Direction: Green when UP, Red when DOWN
  let isTrendingUp = $derived.by(() => {
    if (breakdown.length < 2) return growthPercent >= 0;
    const n = breakdown.length;
    const lastRev = breakdown[n - 1].revenue;
    const prevRev = breakdown[n - 2].revenue;
    const firstRev = breakdown[0].revenue;

    if (lastRev < prevRev) return false;
    if (lastRev > prevRev) return true;
    return lastRev >= firstRev;
  });

  // Dynamic Palette derived from Momentum
  let netColor = $derived(isTrendingUp ? '#10b981' : '#ef4444');
  let netFillRgba = $derived(isTrendingUp ? 'rgba(16, 185, 129, 0.16)' : 'rgba(239, 68, 68, 0.16)');
  let grossColor = $derived(isTrendingUp ? '#6366f1' : '#f97316');
  let grossFillRgba = $derived(
    isTrendingUp ? 'rgba(99, 102, 241, 0.12)' : 'rgba(249, 115, 22, 0.12)'
  );

  // Series visibility toggles
  let showNet = $state(true);
  let showGross = $state(true);

  // 60fps spring interpolation state
  let rafId: number | null = null;
  let lastTime = 0;
  let currentMaxVal = 1000000;
  let animatedNetY: number[] = [];
  let animatedGrossY: number[] = [];

  const timeWindows: { label: string; value: TimeframePeriod }[] = [
    { label: 'Hari', value: 'day' },
    { label: 'Pekan', value: 'week' },
    { label: 'Bulan', value: 'month' },
    { label: 'Tahun', value: 'year' },
    { label: 'Semua', value: 'all' },
  ];

  function formatCompact(val: number): string {
    if (val <= 0) return '0';
    if (val >= 1_000_000_000) {
      const inM = val / 1_000_000_000;
      return `${inM.toFixed(1)}M`;
    }
    if (val >= 10_000_000) {
      const inJt = val / 1_000_000;
      return `${Math.round(inJt)}jt`;
    }
    if (val >= 1_000_000) {
      const inJt = val / 1_000_000;
      return `${inJt.toFixed(1)}jt`;
    }
    if (val >= 1_000) {
      return `${Math.round(val / 1_000)}k`;
    }
    return `${Math.round(val)}`;
  }

  // Fritsch-Carlson Monotone Cubic Hermite Spline
  function drawSpline(ctx: CanvasRenderingContext2D, pts: [number, number][]) {
    if (pts.length < 2) return;
    if (pts.length === 2) {
      ctx.lineTo(pts[1][0], pts[1][1]);
      return;
    }
    const n = pts.length;
    const delta = new Array(n - 1);
    const h = new Array(n - 1);
    for (let i = 0; i < n - 1; i++) {
      h[i] = pts[i + 1][0] - pts[i][0];
      delta[i] = h[i] === 0 ? 0 : (pts[i + 1][1] - pts[i][1]) / h[i];
    }
    const m = new Array(n);
    m[0] = delta[0];
    m[n - 1] = delta[n - 2];
    for (let i = 1; i < n - 1; i++) {
      if (delta[i - 1] * delta[i] <= 0) {
        m[i] = 0;
      } else {
        m[i] = (delta[i - 1] + delta[i]) / 2;
      }
    }
    for (let i = 0; i < n - 1; i++) {
      if (delta[i] === 0) {
        m[i] = 0;
        m[i + 1] = 0;
      } else {
        const alpha = m[i] / delta[i];
        const beta = m[i + 1] / delta[i];
        const s2 = alpha * alpha + beta * beta;
        if (s2 > 9) {
          const s = 3 / Math.sqrt(s2);
          m[i] = s * alpha * delta[i];
          m[i + 1] = s * beta * delta[i];
        }
      }
    }
    for (let i = 0; i < n - 1; i++) {
      const hi = h[i];
      ctx.bezierCurveTo(
        pts[i][0] + hi / 3,
        pts[i][1] + (m[i] * hi) / 3,
        pts[i + 1][0] - hi / 3,
        pts[i + 1][1] - (m[i + 1] * hi) / 3,
        pts[i + 1][0],
        pts[i + 1][1]
      );
    }
  }

  function lerp(current: number, target: number, speed: number, dt: number): number {
    const factor = 1 - Math.pow(1 - speed, Math.max(1, dt) / 16.67);
    return current + (target - current) * factor;
  }

  // --- Reset Viewport to Default Auto-Fit (100%) ---
  function resetScaleAndPan() {
    zoomX = 1.0;
    zoomY = 1.0;
    panX = 0;
    panY = 0;
  }

  // --- Zoom In / Out Controls ---
  function adjustZoomX(deltaFactor: number) {
    if (!containerEl) return;
    const rect = containerEl.getBoundingClientRect();
    const padRight = rect.width < 520 ? 50 : 68;
    const plotW = Math.max(10, rect.width - padRight);

    const oldZoomX = zoomX;
    const newZoomX = Math.max(1.0, Math.min(15.0, oldZoomX * deltaFactor));
    const centerX = plotW / 2;
    const fraction = (centerX - panX) / (plotW * oldZoomX);
    const newPanX = centerX - fraction * (plotW * newZoomX);

    zoomX = newZoomX;
    panX = Math.max(plotW * (1 - newZoomX), Math.min(0, newPanX));
  }

  // --- 60fps Canvas Render Loop ---
  function renderChart(timestamp: number) {
    if (!canvasEl || !containerEl) {
      rafId = requestAnimationFrame(renderChart);
      return;
    }

    const dt = lastTime ? Math.min(32, timestamp - lastTime) : 16.67;
    lastTime = timestamp;

    const ctx = canvasEl.getContext('2d');
    if (!ctx) {
      rafId = requestAnimationFrame(renderChart);
      return;
    }

    const rect = containerEl.getBoundingClientRect();
    const dpr = Math.min(window.devicePixelRatio || 1, 3);
    const w = rect.width;
    const h = rect.height;

    if (canvasEl.width !== Math.floor(w * dpr) || canvasEl.height !== Math.floor(h * dpr)) {
      canvasEl.width = Math.floor(w * dpr);
      canvasEl.height = Math.floor(h * dpr);
    }

    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, w, h);

    const isCompact = w < 520;
    const pad = {
      top: isCompact ? 14 : 20,
      right: isCompact ? 50 : 68,
      bottom: isCompact ? 22 : 28,
      left: 0,
    };

    const plotW = Math.max(10, w - pad.left - pad.right);
    const plotH = Math.max(10, h - pad.top - pad.bottom);

    const n = breakdown.length;
    if (n === 0) {
      ctx.fillStyle = 'rgba(255, 255, 255, 0.35)';
      ctx.font = '400 11.5px system-ui, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(
        isLoading ? 'Memuat data analitik...' : 'Belum ada data transaksi pada periode ini',
        w / 2,
        h / 2
      );
      rafId = requestAnimationFrame(renderChart);
      return;
    }

    const avgDisc = n > 0 ? totalDiscount / n : 0;
    const maxRev = Math.max(...breakdown.map((p) => p.revenue + avgDisc), 1);
    const baseTargetMax = maxRev * 1.12;

    // Tinggikan / Pendekkan via zoomY
    const effectiveTargetMax = Math.max(100, baseTargetMax / zoomY);
    currentMaxVal = lerp(currentMaxVal, effectiveTargetMax, 0.18, dt);

    if (animatedNetY.length !== n) {
      animatedNetY = breakdown.map(() => pad.top + plotH);
      animatedGrossY = breakdown.map(() => pad.top + plotH);
    }

    const netPts: [number, number][] = [];
    const grossPts: [number, number][] = [];

    // Lebarkan / Rapatkan via zoomX & panX
    const contentW = plotW * zoomX;

    for (let i = 0; i < n; i++) {
      const pt = breakdown[i];
      const u = n === 1 ? 0.5 : i / (n - 1);
      const x = pad.left + u * contentW + panX;

      const targetNetY = pad.top + plotH - (Math.max(0, pt.revenue) / currentMaxVal) * plotH + panY;
      const targetGrossY =
        pad.top + plotH - (Math.max(0, pt.revenue + avgDisc) / currentMaxVal) * plotH + panY;

      animatedNetY[i] = lerp(animatedNetY[i], targetNetY, 0.2, dt);
      animatedGrossY[i] = lerp(animatedGrossY[i], targetGrossY, 0.2, dt);

      netPts.push([x, animatedNetY[i]]);
      grossPts.push([x, animatedGrossY[i]]);
    }

    // --- 1. Subtle Y-Axis Gridlines & Price Ticks ---
    const tierCount = isCompact ? 4 : 5;
    const tiers: { val: number; y: number }[] = [];
    for (let t = 0; t <= tierCount; t++) {
      const ratio = t / tierCount;
      const val = currentMaxVal * (1 - ratio);
      const y = pad.top + ratio * plotH + panY;
      if (y >= pad.top - 5 && y <= pad.top + plotH + 5) {
        tiers.push({ val: Math.max(0, val), y });
      }
    }

    ctx.save();
    ctx.setLineDash([2, 3]);
    ctx.lineWidth = 1;
    ctx.font = `${isCompact ? '8.5px' : '9.5px'} "SF Mono", Menlo, monospace`;
    ctx.textAlign = 'right';

    for (const tier of tiers) {
      ctx.strokeStyle = 'rgba(255, 255, 255, 0.06)';
      ctx.beginPath();
      ctx.moveTo(pad.left, tier.y);
      ctx.lineTo(w - pad.right, tier.y);
      ctx.stroke();

      ctx.fillStyle = isHoveringYAxis ? '#ffffff' : 'rgba(255, 255, 255, 0.40)';
      ctx.fillText(formatCompact(tier.val), w - 6, tier.y + 3);
    }
    ctx.restore();

    // --- 2. Clip Region for Curves ---
    ctx.save();
    ctx.beginPath();
    ctx.rect(pad.left, 0, plotW, pad.top + plotH + 2);
    ctx.clip();

    // Gross Curve Area Gradient
    if (showGross && grossPts.length >= 2) {
      ctx.save();
      const grossGrad = ctx.createLinearGradient(0, pad.top, 0, pad.top + plotH);
      grossGrad.addColorStop(0, grossFillRgba);
      grossGrad.addColorStop(1, 'rgba(0, 0, 0, 0.00)');

      ctx.beginPath();
      ctx.moveTo(grossPts[0][0], pad.top + plotH);
      ctx.lineTo(grossPts[0][0], grossPts[0][1]);
      drawSpline(ctx, grossPts);
      ctx.lineTo(grossPts[grossPts.length - 1][0], pad.top + plotH);
      ctx.closePath();
      ctx.fillStyle = grossGrad;
      ctx.fill();
      ctx.restore();
    }

    // Net Curve Area Gradient
    if (showNet && netPts.length >= 2) {
      ctx.save();
      const netGrad = ctx.createLinearGradient(0, pad.top, 0, pad.top + plotH);
      netGrad.addColorStop(0, netFillRgba);
      netGrad.addColorStop(1, 'rgba(0, 0, 0, 0.00)');

      ctx.beginPath();
      ctx.moveTo(netPts[0][0], pad.top + plotH);
      ctx.lineTo(netPts[0][0], netPts[0][1]);
      drawSpline(ctx, netPts);
      ctx.lineTo(netPts[netPts.length - 1][0], pad.top + plotH);
      ctx.closePath();
      ctx.fillStyle = netGrad;
      ctx.fill();
      ctx.restore();
    }

    // Gross Stroke Line
    if (showGross && grossPts.length >= 2) {
      ctx.save();
      ctx.beginPath();
      ctx.moveTo(grossPts[0][0], grossPts[0][1]);
      drawSpline(ctx, grossPts);
      ctx.strokeStyle = grossColor;
      ctx.lineWidth = isCompact ? 1.8 : 2.2;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.stroke();

      const lastGross = grossPts[grossPts.length - 1];
      if (lastGross[0] >= pad.left && lastGross[0] <= pad.left + plotW) {
        ctx.beginPath();
        ctx.arc(lastGross[0], lastGross[1], isCompact ? 3 : 3.5, 0, Math.PI * 2);
        ctx.fillStyle = grossColor;
        ctx.fill();
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 1.2;
        ctx.stroke();
      }
      ctx.restore();
    }

    // Net Stroke Line
    if (showNet && netPts.length >= 2) {
      ctx.save();
      ctx.beginPath();
      ctx.moveTo(netPts[0][0], netPts[0][1]);
      drawSpline(ctx, netPts);
      ctx.strokeStyle = netColor;
      ctx.lineWidth = isCompact ? 2.2 : 2.6;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.stroke();

      const lastNet = netPts[netPts.length - 1];
      if (lastNet[0] >= pad.left && lastNet[0] <= pad.left + plotW) {
        const pulseT = (timestamp % 1500) / 1500;
        const pulseRadius = 3.5 + pulseT * 5.0;
        const pulseAlpha = Math.max(0, (1 - pulseT) * 0.4);

        ctx.beginPath();
        ctx.arc(lastNet[0], lastNet[1], pulseRadius, 0, Math.PI * 2);
        ctx.fillStyle = isTrendingUp
          ? `rgba(16, 185, 129, ${pulseAlpha})`
          : `rgba(239, 68, 68, ${pulseAlpha})`;
        ctx.fill();

        ctx.beginPath();
        ctx.arc(lastNet[0], lastNet[1], isCompact ? 4 : 4.5, 0, Math.PI * 2);
        ctx.fillStyle = netColor;
        ctx.fill();
        ctx.strokeStyle = '#0c0c0e';
        ctx.lineWidth = 2;
        ctx.stroke();
      }
      ctx.restore();
    }

    // End Clip Region
    ctx.restore();

    // --- 3. TradingView Interactive Crosshair & Scrub Guideline ---
    if (mousePos && activeIndex !== null && activeIndex >= 0 && activeIndex < n) {
      const activeX = netPts[activeIndex][0];
      const activeNetY = netPts[activeIndex][1];
      const activeGrossY = grossPts[activeIndex][1];
      const cursorY = mousePos.y;

      if (activeX >= pad.left && activeX <= pad.left + plotW) {
        ctx.save();
        ctx.setLineDash([3, 3]);
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.45)';
        ctx.lineWidth = 1;

        // Vertical crosshair
        ctx.beginPath();
        ctx.moveTo(activeX, pad.top);
        ctx.lineTo(activeX, pad.top + plotH);
        ctx.stroke();

        // Horizontal crosshair across plot area
        if (cursorY >= pad.top && cursorY <= pad.top + plotH) {
          ctx.beginPath();
          ctx.moveTo(pad.left, cursorY);
          ctx.lineTo(pad.left + plotW, cursorY);
          ctx.stroke();
        }

        // Active point dots on curves
        if (showGross) {
          ctx.beginPath();
          ctx.arc(activeX, activeGrossY, 3.5, 0, Math.PI * 2);
          ctx.fillStyle = grossColor;
          ctx.fill();
          ctx.strokeStyle = '#ffffff';
          ctx.lineWidth = 1.5;
          ctx.stroke();
        }

        if (showNet) {
          ctx.beginPath();
          ctx.arc(activeX, activeNetY, 4.5, 0, Math.PI * 2);
          ctx.fillStyle = netColor;
          ctx.fill();
          ctx.strokeStyle = '#0c0c0e';
          ctx.lineWidth = 2;
          ctx.stroke();
        }

        ctx.restore();

        // TradingView Y-Axis Floating Price Badge
        if (cursorY >= pad.top && cursorY <= pad.top + plotH) {
          const hoveredVal = Math.max(
            0,
            ((pad.top + plotH - cursorY + panY) / plotH) * currentMaxVal
          );
          const badgeText = formatCompact(hoveredVal);

          ctx.save();
          ctx.font = `600 ${isCompact ? '9px' : '10px'} "SF Mono", Menlo, monospace`;
          const badgeW = ctx.measureText(badgeText).width + 12;
          const badgeH = 18;
          const badgeX = w - badgeW - 2;
          const badgeY = cursorY - badgeH / 2;

          ctx.fillStyle = '#27272a';
          ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
          ctx.lineWidth = 1;
          ctx.beginPath();
          ctx.roundRect(badgeX, badgeY, badgeW, badgeH, 4);
          ctx.fill();
          ctx.stroke();

          ctx.fillStyle = '#ffffff';
          ctx.textAlign = 'center';
          ctx.fillText(badgeText, badgeX + badgeW / 2, badgeY + badgeH / 2 + 3.5);
          ctx.restore();
        }

        // TradingView X-Axis Floating Date Badge
        const dateLabel = breakdown[activeIndex].label;
        ctx.save();
        ctx.font = `600 ${isCompact ? '9px' : '10px'} "SF Mono", Menlo, monospace`;
        const dateW = ctx.measureText(dateLabel).width + 14;
        const dateH = 18;
        const dateX = Math.max(pad.left, Math.min(pad.left + plotW - dateW, activeX - dateW / 2));
        const dateY = h - pad.bottom + 4;

        ctx.fillStyle = '#27272a';
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.roundRect(dateX, dateY, dateW, dateH, 4);
        ctx.fill();
        ctx.stroke();

        ctx.fillStyle = '#ffffff';
        ctx.textAlign = 'center';
        ctx.fillText(dateLabel, dateX + dateW / 2, dateY + dateH / 2 + 3.5);
        ctx.restore();
      }
    }

    // --- 4. Clean Monospace Time Axis Labels ---
    ctx.save();
    ctx.font = `${isCompact ? '8.5px' : '9.5px'} "SF Mono", Menlo, monospace`;

    const minLabelDist = isCompact ? 60 : 75;
    const avgPointDist = (plotW * zoomX) / Math.max(1, n - 1);
    const step = Math.max(1, Math.ceil(minLabelDist / avgPointDist));

    for (let i = 0; i < n; i++) {
      const isVisible = i % step === 0 || i === n - 1;
      if (isVisible) {
        const x = netPts[i][0];
        if (x >= pad.left - 10 && x <= pad.left + plotW + 10) {
          const label = breakdown[i].label;
          const isSelected = activeIndex === i;

          if (!isSelected) {
            ctx.fillStyle = isHoveringXAxis
              ? 'rgba(255, 255, 255, 0.65)'
              : 'rgba(255, 255, 255, 0.35)';
            ctx.font = `${isCompact ? '8.5px' : '9.5px'} "SF Mono", Menlo, monospace`;
            ctx.textAlign = 'center';
            ctx.fillText(label, x, h - 5);
          }
        }
      }
    }
    ctx.restore();

    // --- 5. Axis Boundary Lines ---
    ctx.save();
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)';
    ctx.lineWidth = 1;

    // Right Y-axis border
    ctx.beginPath();
    ctx.moveTo(w - pad.right, pad.top);
    ctx.lineTo(w - pad.right, pad.top + plotH);
    ctx.stroke();

    // Bottom X-axis border
    ctx.beginPath();
    ctx.moveTo(pad.left, pad.top + plotH);
    ctx.lineTo(w - pad.right, pad.top + plotH);
    ctx.stroke();
    ctx.restore();

    rafId = requestAnimationFrame(renderChart);
  }

  // --- Interactive Hit-Testing & Pointer Position ---
  function updateActivePoint(clientX: number, clientY: number) {
    if (!containerEl || breakdown.length === 0) return;
    const rect = containerEl.getBoundingClientRect();
    const isCompact = rect.width < 520;
    const padRight = isCompact ? 50 : 68;
    const padBottom = isCompact ? 22 : 28;
    const plotW = Math.max(10, rect.width - padRight);

    const localX = clientX - rect.left;
    const localY = clientY - rect.top;

    mousePos = { x: localX, y: localY };

    // Axis hover detection
    isHoveringYAxis = localX >= rect.width - padRight && localY <= rect.height - padBottom;
    isHoveringXAxis = localY >= rect.height - padBottom && localX <= rect.width - padRight;

    if (isHoveringYAxis || isHoveringXAxis) {
      activeIndex = null;
      return;
    }

    const n = breakdown.length;
    const contentW = plotW * zoomX;

    let closestIdx = 0;
    let minDiff = Infinity;

    for (let i = 0; i < n; i++) {
      const u = n === 1 ? 0.5 : i / (n - 1);
      const screenX = u * contentW + panX;
      const diff = Math.abs(screenX - localX);
      if (diff < minDiff) {
        minDiff = diff;
        closestIdx = i;
      }
    }

    activeIndex = closestIdx;
  }

  // --- Pointer Down Event Dispatcher ---
  function handlePointerDown(e: MouseEvent | TouchEvent) {
    if (!containerEl) return;
    const rect = containerEl.getBoundingClientRect();
    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;
    const localX = clientX - rect.left;
    const localY = clientY - rect.top;

    const isCompact = rect.width < 520;
    const padRight = isCompact ? 50 : 68;
    const padBottom = isCompact ? 22 : 28;

    dragStartPos = { x: clientX, y: clientY };
    dragStartZoom = { x: zoomX, y: zoomY };
    dragStartPan = { x: panX, y: panY };

    if (localX >= rect.width - padRight && localY <= rect.height - padBottom) {
      // 1. Drag Y-Axis to scale price height (Tinggikan / Pendekkan Skala Y)
      activeDragMode = 'axis-y';
      window.addEventListener('mousemove', handleGlobalPointerMove);
      window.addEventListener('mouseup', handleGlobalPointerUp);
      window.addEventListener('touchmove', handleGlobalPointerMove, { passive: false });
      window.addEventListener('touchend', handleGlobalPointerUp);
    } else if (localY >= rect.height - padBottom && localX <= rect.width - padRight) {
      // 2. Drag X-Axis to scale time width (Lebarkan / Rapatkan Skala X)
      activeDragMode = 'axis-x';
      window.addEventListener('mousemove', handleGlobalPointerMove);
      window.addEventListener('mouseup', handleGlobalPointerUp);
      window.addEventListener('touchmove', handleGlobalPointerMove, { passive: false });
      window.addEventListener('touchend', handleGlobalPointerUp);
    } else if (localX < rect.width - padRight && localY < rect.height - padBottom) {
      // 3. Drag Main Canvas to Pan (Geser Grafik)
      activeDragMode = 'canvas';
      window.addEventListener('mousemove', handleGlobalPointerMove);
      window.addEventListener('mouseup', handleGlobalPointerUp);
      window.addEventListener('touchmove', handleGlobalPointerMove, { passive: false });
      window.addEventListener('touchend', handleGlobalPointerUp);
    }
  }

  // --- Global Pointer Move for Dragging ---
  function handleGlobalPointerMove(e: MouseEvent | TouchEvent) {
    if (activeDragMode === 'none') return;
    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;

    if (!containerEl) return;
    const rect = containerEl.getBoundingClientRect();
    const isCompact = rect.width < 520;
    const padRight = isCompact ? 50 : 68;
    const plotW = Math.max(10, rect.width - padRight);

    if (activeDragMode === 'axis-y') {
      // Tinggikan / Pendekkan skala Y
      const dy = dragStartPos.y - clientY;
      const newZoomY = Math.max(0.4, Math.min(6.0, dragStartZoom.y * (1 + dy / 100)));
      zoomY = newZoomY;
    } else if (activeDragMode === 'axis-x') {
      // Lebarkan / Rapatkan skala X
      const dx = clientX - dragStartPos.x;
      const newZoomX = Math.max(1.0, Math.min(15.0, dragStartZoom.x * (1 + dx / 120)));
      zoomX = newZoomX;
      panX = Math.max(plotW * (1 - newZoomX), Math.min(0, panX));
    } else if (activeDragMode === 'canvas') {
      // Geser (Pan) Horizontal & Vertikal
      const dx = clientX - dragStartPos.x;
      const dy = clientY - dragStartPos.y;

      const minPanX = plotW * (1 - zoomX);
      panX = Math.max(minPanX, Math.min(0, dragStartPan.x + dx));

      if (zoomY > 1.0) {
        const maxPanY = 150 * (zoomY - 1.0);
        panY = Math.max(-maxPanY, Math.min(maxPanY, dragStartPan.y + dy));
      }
    }
  }

  // --- Global Pointer Up ---
  function handleGlobalPointerUp() {
    activeDragMode = 'none';
    window.removeEventListener('mousemove', handleGlobalPointerMove);
    window.removeEventListener('mouseup', handleGlobalPointerUp);
    window.removeEventListener('touchmove', handleGlobalPointerMove);
    window.removeEventListener('touchend', handleGlobalPointerUp);
  }

  // --- Mouse Wheel Zoom ---
  function handleWheel(e: WheelEvent) {
    if (!containerEl) return;
    e.preventDefault();

    const rect = containerEl.getBoundingClientRect();
    const isCompact = rect.width < 520;
    const padRight = isCompact ? 50 : 68;
    const plotW = Math.max(10, rect.width - padRight);

    const localX = Math.max(0, Math.min(plotW, e.clientX - rect.left));
    const zoomFactor = e.deltaY < 0 ? 1.15 : 1 / 1.15;

    const oldZoomX = zoomX;
    const newZoomX = Math.max(1.0, Math.min(15.0, oldZoomX * zoomFactor));

    const fraction = (localX - panX) / (plotW * oldZoomX);
    const newPanX = localX - fraction * (plotW * newZoomX);

    zoomX = newZoomX;
    panX = Math.max(plotW * (1 - newZoomX), Math.min(0, newPanX));
  }

  // --- Mouse Move Inside Chart ---
  function handleMouseMove(e: MouseEvent) {
    if (activeDragMode === 'none') {
      updateActivePoint(e.clientX, e.clientY);
    }
  }

  function handleMouseLeave() {
    if (activeDragMode === 'none') {
      mousePos = null;
      activeIndex = null;
      isHoveringYAxis = false;
      isHoveringXAxis = false;
    }
  }

  onMount(() => {
    rafId = requestAnimationFrame(renderChart);
  });

  onDestroy(() => {
    if (rafId) {
      cancelAnimationFrame(rafId);
    }
    handleGlobalPointerUp();
  });

  // Active scrubbed point values
  let activePoint = $derived(
    activeIndex !== null && breakdown[activeIndex] ? breakdown[activeIndex] : null
  );
  let activeGrossVal = $derived.by(() => {
    if (!activePoint) return 0;
    const avgDisc = breakdown.length > 0 ? totalDiscount / breakdown.length : 0;
    return activePoint.revenue + avgDisc;
  });

  // Displayed top-level numbers
  let displayedRevenue = $derived(activePoint ? activePoint.revenue : totalRevenue);
  let displayedGross = $derived(activePoint ? activeGrossVal : totalRevenue + totalDiscount);
  let displayedOrders = $derived(activePoint ? activePoint.orders_count : totalOrders);
  let displayedAOV = $derived(
    activePoint && activePoint.average_ticket > 0 ? activePoint.average_ticket : averageOrderValue
  );

  // Dynamic cursor based on hover region
  let activeCursorClass = $derived.by(() => {
    if (activeDragMode === 'axis-y') return 'cursor-row-resize';
    if (activeDragMode === 'axis-x') return 'cursor-col-resize';
    if (activeDragMode === 'canvas') return 'cursor-grabbing';
    if (isHoveringYAxis) return 'cursor-row-resize';
    if (isHoveringXAxis) return 'cursor-col-resize';
    if (zoomX > 1.0 || zoomY > 1.0) return 'cursor-grab';
    return 'cursor-crosshair';
  });

  let isAlteredScale = $derived(
    zoomX > 1.02 || zoomY > 1.02 || zoomY < 0.98 || panX !== 0 || panY !== 0
  );
</script>

<div class="w-full font-sans select-none">
  <!-- TradingView-Grade Interactive Dark Chart Card -->
  <div
    class="relative space-y-4 overflow-hidden rounded-2xl border border-white/10 bg-[#0c0c0e] p-4 text-white shadow-2xl transition-all sm:rounded-3xl sm:p-6"
  >
    <!-- Top Header: Live Metric Overlay & Time Window Tabs -->
    <div
      class="flex flex-col gap-3 border-b border-white/10 pb-1 sm:flex-row sm:items-center sm:justify-between"
    >
      <!-- Live Value Overlay -->
      <div class="min-w-0">
        <div class="flex items-center gap-2 font-mono text-[11px] text-white/50">
          <span>{activePoint ? activePoint.label : 'Pendapatan Bersih'}</span>
          <span class="text-white/30">•</span>
          <span class="text-white/40">{activePoint ? 'Titik Terpilih' : periodLabel}</span>
        </div>

        <div class="mt-1 flex flex-wrap items-baseline gap-2.5">
          <span
            class="font-mono text-2xl font-bold tracking-tight transition-colors duration-150 sm:text-3xl lg:text-4xl {isTrendingUp
              ? 'text-[#10b981]'
              : 'text-[#ef4444]'}"
          >
            {formatRupiah(displayedRevenue)}
          </span>

          {#if !activePoint}
            <span
              class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-mono text-[11px] font-medium sm:text-xs {growthPercent >=
              0
                ? 'bg-[#10b981]/15 text-[#10b981]'
                : 'bg-[#ef4444]/15 text-[#ef4444]'}"
            >
              {growthPercent >= 0 ? '↑' : '↓'}
              {growthPercent >= 0 ? `+${growthPercent}%` : `${growthPercent}%`}
              {growthLabel}
            </span>
          {:else}
            <span class="font-mono text-[11px] text-white/50">
              (Gross {formatRupiah(displayedGross)})
            </span>
          {/if}
        </div>
      </div>

      <!-- Time Windows Pills -->
      <div
        class="flex shrink-0 items-center self-start rounded-full border border-white/10 bg-white/5 p-1 sm:self-auto"
      >
        {#each timeWindows as tw}
          <button
            type="button"
            onclick={() => onSelectTimeframe(tw.value)}
            class={`cursor-pointer rounded-full px-3 py-1 text-xs font-medium transition-all ${
              selectedTimeframe === tw.value
                ? 'bg-white font-semibold text-[#0c0c0e] shadow-xs'
                : 'text-white/60 hover:bg-white/5 hover:text-white'
            }`}
          >
            {tw.label}
          </button>
        {/each}
      </div>
    </div>

    <!-- Secondary Metrics & TradingView Controls Bar -->
    <div class="flex flex-wrap items-center justify-between gap-3 font-mono text-xs">
      <!-- Minimalist Stats Strip -->
      <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-white/70">
        <div class="flex items-center gap-1.5">
          <span class="text-[10px] text-white/40 uppercase">Kotor:</span>
          <span class="font-semibold text-white/90">{formatRupiah(displayedGross)}</span>
        </div>
        <div class="hidden h-3.5 w-px bg-white/15 sm:block"></div>
        <div class="flex items-center gap-1.5">
          <span class="text-[10px] text-white/40 uppercase">Volume:</span>
          <span class="font-semibold text-white/90"
            >{displayedOrders.toLocaleString('id-ID')} order</span
          >
        </div>
        <div class="hidden h-3.5 w-px bg-white/15 sm:block"></div>
        <div class="flex items-center gap-1.5">
          <span class="text-[10px] text-white/40 uppercase">AOV:</span>
          <span class="font-semibold text-white/90">{formatRupiah(displayedAOV)}</span>
        </div>
      </div>

      <!-- Series Visibility & TradingView Scaling Tools -->
      <div class="flex shrink-0 flex-wrap items-center gap-1.5">
        <!-- Series Toggles -->
        <button
          type="button"
          onclick={() => (showNet = !showNet)}
          class={`flex cursor-pointer items-center gap-1.5 rounded-full border px-2 py-0.5 text-[10.5px] font-medium transition-all ${
            showNet
              ? isTrendingUp
                ? 'border-[#10b981]/40 bg-[#10b981]/15 text-[#10b981]'
                : 'border-[#ef4444]/40 bg-[#ef4444]/15 text-[#ef4444]'
              : 'border-white/10 bg-white/5 text-white/40 hover:text-white/70'
          }`}
          title="Tampilkan / Sembunyikan garis omzet bersih"
        >
          <span class="size-1.5 rounded-full {isTrendingUp ? 'bg-[#10b981]' : 'bg-[#ef4444]'}"
          ></span>
          <span>Bersih</span>
        </button>

        <button
          type="button"
          onclick={() => (showGross = !showGross)}
          class={`flex cursor-pointer items-center gap-1.5 rounded-full border px-2 py-0.5 text-[10.5px] font-medium transition-all ${
            showGross
              ? isTrendingUp
                ? 'border-[#6366f1]/40 bg-[#6366f1]/15 text-[#818cf8]'
                : 'border-[#f97316]/40 bg-[#f97316]/15 text-[#fb923c]'
              : 'border-white/10 bg-white/5 text-white/40 hover:text-white/70'
          }`}
          title="Tampilkan / Sembunyikan garis omzet kotor"
        >
          <span class="size-1.5 rounded-full {isTrendingUp ? 'bg-[#6366f1]' : 'bg-[#f97316]'}"
          ></span>
          <span>Kotor</span>
        </button>

        <!-- TradingView Action Buttons -->
        <div class="ml-1 flex items-center gap-1 border-l border-white/10 pl-2">
          <!-- Zoom Out X (Rapatkan Skala X) -->
          <button
            type="button"
            onclick={() => adjustZoomX(1 / 1.25)}
            disabled={zoomX <= 1.0}
            class="cursor-pointer rounded-md border border-white/10 bg-white/5 p-1 text-white/80 transition-all hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-30"
            title="Persempit rentang waktu (Zoom Out)"
          >
            <ZoomOut class="size-3.5" />
          </button>

          <!-- Zoom In X (Lebarkan Skala X) -->
          <button
            type="button"
            onclick={() => adjustZoomX(1.25)}
            disabled={zoomX >= 15.0}
            class="cursor-pointer rounded-md border border-white/10 bg-white/5 p-1 text-white/80 transition-all hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-30"
            title="Lebarkan rentang waktu (Zoom In)"
          >
            <ZoomIn class="size-3.5" />
          </button>

          <!-- Auto Fit / Reset Button -->
          <button
            type="button"
            onclick={resetScaleAndPan}
            class={`flex cursor-pointer items-center gap-1 rounded-md border px-2 py-1 text-[10px] font-semibold transition-all ${
              isAlteredScale
                ? 'border-amber-500/40 bg-amber-500/20 text-amber-300 shadow-xs'
                : 'border-white/10 bg-white/5 text-white/50 hover:text-white/80'
            }`}
            title="Reset skala &amp; posisi ke default auto-fit (Double-click juga bisa)"
          >
            <RotateCcw class="size-3" />
            <span>Auto</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 60fps Native Canvas Area with TradingView Drag Interaction -->
    <div
      bind:this={containerEl}
      role="application"
      aria-label="Grafik tren penjualan interaktif TradingView"
      class="relative h-52 w-full touch-none select-none sm:h-64 md:h-72 {activeCursorClass}"
    >
      <canvas
        bind:this={canvasEl}
        onmousedown={handlePointerDown}
        ontouchstart={handlePointerDown}
        onmousemove={handleMouseMove}
        onmouseleave={handleMouseLeave}
        onwheel={handleWheel}
        ondblclick={resetScaleAndPan}
        class="block size-full"
      ></canvas>

      <!-- TradingView Interactive Axis Hover Tooltip Hints -->
      {#if isHoveringYAxis}
        <div
          class="pointer-events-none absolute top-2 right-1 rounded-md border border-white/20 bg-black/80 px-2 py-0.5 font-mono text-[9.5px] text-white/80 shadow-md backdrop-blur-xs"
        >
          ↕ Tarik vertikal untuk tinggikan skala Y
        </div>
      {/if}

      {#if isHoveringXAxis}
        <div
          class="pointer-events-none absolute bottom-1 left-2 rounded-md border border-white/20 bg-black/80 px-2 py-0.5 font-mono text-[9.5px] text-white/80 shadow-md backdrop-blur-xs"
        >
          ↔ Tarik horizontal untuk lebarkan skala X
        </div>
      {/if}

      <!-- Floating Zoom Ratio Indicator -->
      {#if isAlteredScale}
        <div
          class="pointer-events-none absolute top-2 left-2 flex items-center gap-1.5 rounded-md border border-amber-500/30 bg-[#17171c]/80 px-2 py-0.5 font-mono text-[9.5px] text-amber-300/90 shadow-md backdrop-blur-xs"
        >
          <span>Skala: X:{zoomX.toFixed(1)}x | Y:{zoomY.toFixed(1)}x</span>
          <span class="text-white/40">• Klik 'Auto' atau Double-Click untuk reset</span>
        </div>
      {/if}
    </div>
  </div>
</div>
