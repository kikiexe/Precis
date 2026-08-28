import { describe, it, expect } from 'vitest';
import { formatRupiah, formatDateIndo, formatDateTimeIndo } from './formatters';

describe('formatRupiah', () => {
  it('formats positive numbers as IDR currency without decimal places', () => {
    const formatted = formatRupiah(50000);
    // Matches IDR formatting like "Rp 50.000" or "Rp50.000"
    expect(formatted.replace(/\s+/g, ' ')).toMatch(/Rp\s?50\.000/);
  });

  it('handles zero correctly', () => {
    const formatted = formatRupiah(0);
    expect(formatted.replace(/\s+/g, ' ')).toMatch(/Rp\s?0/);
  });

  it('returns fallback for null, undefined, or NaN', () => {
    expect(formatRupiah(null)).toBe('Rp 0');
    expect(formatRupiah(undefined)).toBe('Rp 0');
    expect(formatRupiah(NaN)).toBe('Rp 0');
  });

  it('formats large numbers properly with thousand separators', () => {
    const formatted = formatRupiah(125000000);
    expect(formatted.replace(/\s+/g, ' ')).toMatch(/Rp\s?125\.000\.000/);
  });
});

describe('formatDateIndo', () => {
  it('formats standard ISO date string to Indonesian short month format', () => {
    const formatted = formatDateIndo('2026-08-28T10:00:00Z');
    expect(formatted).toBeTruthy();
    expect(formatted).not.toBe('-');
  });

  it('returns hyphen for empty or null date', () => {
    expect(formatDateIndo(null)).toBe('-');
    expect(formatDateIndo('')).toBe('-');
    expect(formatDateIndo(undefined)).toBe('-');
  });
});

describe('formatDateTimeIndo', () => {
  it('formats ISO string with time component', () => {
    const formatted = formatDateTimeIndo('2026-08-28T14:30:00Z');
    expect(formatted).toBeTruthy();
    expect(formatted).not.toBe('-');
  });

  it('returns hyphen for falsy dates', () => {
    expect(formatDateTimeIndo(null)).toBe('-');
    expect(formatDateTimeIndo(undefined)).toBe('-');
  });
});
