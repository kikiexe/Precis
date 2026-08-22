<script lang="ts">
  import { ChevronDown } from 'lucide-svelte';

  interface FaqItem {
    question: string;
    answer: string;
  }

  const faqs: FaqItem[] = [
    {
      question: 'Apakah saya perlu membeli tablet atau perangkat keras baru yang mahal?',
      answer:
        'Tidak perlu. Précis dapat langsung diakses melalui tablet Android, iPad, laptop, maupun smartphone apa pun yang sudah Anda miliki via peramban web modern.'
    },
    {
      question: 'Bagaimana jika koneksi internet Wi-Fi kedai tiba-tiba putus di jam sibuk?',
      answer:
        'Aplikasi kasir Précis tetap beroperasi 100% normal secara offline. Kasir tetap bisa memilih menu, menghitung kembalian, dan mencetak struk thermal Bluetooth. Saat internet pulih, seluruh pesanan otomatis terkirim ke server.'
    },
    {
      question: 'Bagaimana cara Précis mencegah karyawan melakukan kecurangan titip absen?',
      answer:
        'Karyawan wajib mengambil foto selfie langsung di tempat via kamera HP saat jam shift dimulai (sistem menolak foto dari galeri). Koordinat GPS toko dan jam kedatangan WIB dicetak menyatu pada foto sehingga Store Manager dapat mengauditnya setiap pagi.'
    },
    {
      question: 'Bagaimana proses perhitungan denda keterlambatan dan upah lembur?',
      answer:
        'Sistem otomatis mencatat selisih menit kedatangan aktual dengan jadwal shift yang ditentukan. Di akhir bulan, kalkulator payroll langsung menghitung nominal denda keterlambatan, upah lembur, dan potongan cicilan kasbon tanpa perlu dihitung manual di Excel.'
    },
    {
      question: 'Apakah format ekspor gaji bisa langsung dipakai untuk transfer bank?',
      answer:
        'Ya. Précis menyediakan file ekspor CSV/Excel yang sudah diformat sesuai standar pengunggahan massal payroll perbankan nasional seperti BCA Corporate/KlikBCA, Mandiri MCM, dan BRI.'
    },
    {
      question: 'Bisakah saya mengelola lebih dari satu cabang kedai kopi dalam satu akun?',
      answer:
        'Tentu saja. Dengan satu akun Owner, Anda dapat memantau omzet harian, stok menu, dan kehadiran karyawan di seluruh cabang sekaligus, sementara Kepala Toko (Store Manager) hanya memiliki akses untuk cabang yang ditugaskan.'
    }
  ];

  let openIndex = $state<number | null>(0);

  function toggleFaq(index: number) {
    openIndex = openIndex === index ? null : index;
  }
</script>

<section id="faq" class="bg-[#f4f4f4] py-20 lg:py-28 border-b border-[#e0e0e0]">
  <div class="max-w-[1584px] mx-auto px-4 lg:px-8">
    <div class="max-w-3xl mb-16">
      <span class="text-sm text-[#525252] block mb-4 font-mono">
        Tanya Jawab Operasional
      </span>
      <h2 class="font-display-lg text-[#161616] tracking-tight mb-6">
        Pertanyaan umum dari pemilik bisnis F&amp;B.
      </h2>
      <p class="font-subhead text-[#525252]">
        Semua jawaban praktis seputar kemudahan implementasi Précis di kedai kopi atau resto Anda.
      </p>
    </div>

    <!-- FAQ Accordion Grid -->
    <div class="max-w-4xl space-y-4">
      {#each faqs as faq, i}
        <div class="bg-white border border-[#e0e0e0] transition-colors">
          <button
            type="button"
            onclick={() => toggleFaq(i)}
            class="w-full p-6 text-left flex items-center justify-between gap-4 font-card-title text-base lg:text-lg text-[#161616] hover:text-[#0f62fe] transition-colors"
          >
            <span>{faq.question}</span>
            <ChevronDown
              class={`w-5 h-5 text-[#525252] shrink-0 transition-transform duration-300 ${
                openIndex === i ? 'rotate-180 text-[#0f62fe]' : ''
              }`}
            />
          </button>

          {#if openIndex === i}
            <div class="px-6 pb-6 pt-2 text-sm font-body-sm text-[#525252] leading-relaxed border-t border-[#f4f4f4]">
              {faq.answer}
            </div>
          {/if}
        </div>
      {/each}
    </div>
  </div>
</section>
