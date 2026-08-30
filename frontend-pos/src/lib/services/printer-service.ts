import type { OfflineOrder } from '../types/pos';

export interface PrintReceiptOptions {
  storeName: string;
  storeAddress: string;
  storePhone: string;
  footerNote: string;
}

export const defaultPrintOptions: PrintReceiptOptions = {
  storeName: 'PRÉCIS COFFEE & EATERY',
  storeAddress: 'Jl. Kaliurang KM 5.2 No. 18, Sleman, Yogyakarta',
  storePhone: 'Telp: 0812-3456-7890',
  footerNote: 'Terima kasih atas kunjungan Anda!\nBarang yang dibeli tidak dapat ditukar.',
};

export function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
}

export function generateReceiptText(
  order: OfflineOrder,
  options: PrintReceiptOptions = defaultPrintOptions
): string {
  const line = '--------------------------------';
  const doubleLine = '================================';
  const center = (text: string, width = 32) => {
    const pad = Math.max(0, Math.floor((width - text.length) / 2));
    return ' '.repeat(pad) + text;
  };
  const row = (left: string, right: string, width = 32) => {
    const space = Math.max(1, width - left.length - right.length);
    return left + ' '.repeat(space) + right;
  };

  let text = '';
  text += center(options.storeName) + '\n';
  text += center(options.storeAddress.substring(0, 32)) + '\n';
  text += center(options.storePhone) + '\n';
  text += doubleLine + '\n';
  text += row('No:', order.order_number) + '\n';
  text += row('Tgl:', new Date(order.created_at).toLocaleString('id-ID')) + '\n';
  text += row('Kasir:', order.cashier_name) + '\n';
  text +=
    row(
      'Tipe:',
      order.order_type === 'DINE_IN'
        ? `Dine In (Meja ${order.table_number || '-'})`
        : order.order_type
    ) + '\n';
  if (order.customer_name) {
    text += row('Pelanggan:', order.customer_name) + '\n';
  }
  text += row('Metode:', order.payment_method) + '\n';
  text += line + '\n';

  order.items.forEach((item) => {
    text += `${item.product_name}\n`;
    text +=
      row(
        `  ${item.quantity} x ${formatCurrency(item.unit_price)}`,
        formatCurrency(item.subtotal)
      ) + '\n';
    if (item.notes) {
      text += `  * ${item.notes}\n`;
    }
  });

  text += line + '\n';
  text += row('Subtotal:', formatCurrency(order.total_amount)) + '\n';
  if (order.discount_amount > 0) {
    text += row('Diskon:', `-${formatCurrency(order.discount_amount)}`) + '\n';
  }
  if (order.tax_type === 'EXCLUSIVE' && (order.tax_amount ?? 0) > 0) {
    const taxLabel = `${order.tax_name || 'PB1'} (${order.tax_rate ?? 0}%):`;
    text += row(taxLabel, formatCurrency(order.tax_amount || 0)) + '\n';
  }
  text += row('TOTAL:', formatCurrency(order.final_amount)) + '\n';
  if (order.tax_type === 'INCLUSIVE' && (order.tax_amount ?? 0) > 0) {
    text += row(`*Inc. ${order.tax_name || 'PB1'} (${order.tax_rate ?? 0}%):`, formatCurrency(order.tax_amount || 0)) + '\n';
  }
  if (order.cash_tendered) {
    text += row('Bayar Tunai:', formatCurrency(order.cash_tendered)) + '\n';
    text += row('Kembalian:', formatCurrency(order.change_amount || 0)) + '\n';
  }
  text += doubleLine + '\n';
  text += center('LOKAL SYNC ID:') + '\n';
  text += center(order.client_order_id.substring(0, 18) + '...') + '\n';
  text += '\n' + center('Terima kasih & Selamat Datang Kembali') + '\n';

  return text;
}

export async function simulatePrint(_order: OfflineOrder): Promise<boolean> {
  return new Promise((resolve) => {
    setTimeout(() => {
      resolve(true);
    }, 500);
  });
}
