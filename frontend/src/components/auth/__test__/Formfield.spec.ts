// ===== [IMPORTS] =====
import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import FormField from '../FormField.vue';

// ===== [HELPERS] =====
const mountFormField = (props = {}) => {
  return mount(FormField, {
    props: {
      modelValue: '',
      label: 'NIK (16 Digit)',
      ...props,
    },
  });
};

// ===== [TEST SUITE] =====
describe('FormField Component', () => {
  // ===== [1. HAPPY PATH — 3 test] =====
  describe('Happy Path — Render & Interaksi Normal', () => {
    it('merender label sesuai props', () => {
      const wrapper = mountFormField({ label: 'Nama Lengkap' });
      expect(wrapper.find('label').text()).toBe('Nama Lengkap');
    });

    it('emit update:modelValue saat user mengetik', async () => {
      const wrapper = mountFormField();
      const input = wrapper.find('input');

      await input.setValue('6372010101010001');

      expect(wrapper.emitted('update:modelValue')).toBeTruthy();
      expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['6372010101010001']);
    });

    it('emit blur saat input kehilangan fokus', async () => {
      const wrapper = mountFormField();
      await wrapper.find('input').trigger('blur');
      expect(wrapper.emitted('blur')).toBeTruthy();
    });
  });

  // ===== [2. SAD PATH — 2 test] =====
  describe('Sad Path — Tampilan Error', () => {
    it('menampilkan errorMessage saat error=true', () => {
      const wrapper = mountFormField({
        error: true,
        errorMessage: 'Wajib diisi minimal 10 digit',
      });

      const errorEl = wrapper.find('p');
      expect(errorEl.exists()).toBe(true);
      expect(errorEl.text()).toBe('Wajib diisi minimal 10 digit');
    });

    it('tidak menampilkan error saat error=false', () => {
      const wrapper = mountFormField({
        error: false,
        errorMessage: 'Pesan ini tidak muncul',
      });

      expect(wrapper.find('p').exists()).toBe(false);
    });
  });

  // ===== [3. BOUNDARY — 2 test] =====
  describe('Boundary — Batasan Input', () => {
    it('maxlength string "16" diterapkan ke input', () => {
      const wrapper = mountFormField({ maxlength: '16' });
      expect(wrapper.find('input').attributes('maxlength')).toBe('16');
    });

    it('maxlength number 6 diterapkan ke input', () => {
      const wrapper = mountFormField({ maxlength: 6 });
      expect(wrapper.find('input').attributes('maxlength')).toBe('6');
    });
  });

  // ===== [4. EDGE CASE — 1 test] =====
  describe('Edge Case — Props Ekstrem', () => {
    it('modelValue undefined tidak crash', () => {
      const wrapper = mount(FormField, {
        props: { modelValue: undefined as any, label: 'Test' },
      });

      expect(wrapper.find('input').exists()).toBe(true);
    });
  });

  // ===== [5. NULL/EMPTY — 2 test] =====
  describe('Null/Empty — State Kosong', () => {
    it('errorMessage kosong tidak merender tag <p>', () => {
      const wrapper = mountFormField({
        error: true,
        errorMessage: '',
      });

      expect(wrapper.find('p').exists()).toBe(false);
    });

    it('label tetap tampil meski modelValue ""', () => {
      const wrapper = mountFormField({ modelValue: '' });
      expect(wrapper.find('label').text()).toBeTruthy();
    });
  });

  // ===== [6. DATA TYPE — 2 test] =====
  describe('Data Type — Tipe Input', () => {
    it('type="tel" menghasilkan inputmode="numeric"', () => {
      const wrapper = mountFormField({ type: 'tel' });
      expect(wrapper.find('input').attributes('inputmode')).toBe('numeric');
    });

    it('type="text" menghasilkan inputmode="text"', () => {
      const wrapper = mountFormField({ type: 'text' });
      expect(wrapper.find('input').attributes('inputmode')).toBe('text');
    });
  });

  // ===== [7. EQUIVALENCE PARTITION — 2 test] =====
  describe('Equivalence Partition — Grup Status', () => {
    it('disabled=true → input disabled, class abu-abu', () => {
      const wrapper = mountFormField({ disabled: true });
      const input = wrapper.find('input');

      expect(input.attributes('disabled')).toBe('');
      expect(input.classes()).toContain('bg-gray-200');
    });

    it('disabled=false → input aktif, class normal', () => {
      const wrapper = mountFormField({ disabled: false });
      const input = wrapper.find('input');

      expect(input.attributes('disabled')).toBeUndefined();
      expect(input.classes()).not.toContain('bg-gray-200');
    });
  });

  // ===== [8. STATE TRANSITION — 1 test] =====
  describe('State Transition — Perubahan Props', () => {
    it('error true → false menyembunyikan pesan error', async () => {
      const wrapper = mountFormField({
        error: true,
        errorMessage: 'Error muncul',
      });

      expect(wrapper.find('p').exists()).toBe(true);

      await wrapper.setProps({ error: false });
      expect(wrapper.find('p').exists()).toBe(false);
    });
  });

  // ===== [9. CONCURRENCY — 1 test] =====
  describe('Concurrency — Input Cepat', () => {
    it('ketik cepat tidak menghasilkan emit duplikat', async () => {
      const wrapper = mountFormField();
      const input = wrapper.find('input');

      await input.setValue('6');
      await input.setValue('63');
      await input.setValue('637');

      const emits = wrapper.emitted('update:modelValue') || [];
      expect(emits.length).toBe(3);
      expect(emits[2]).toEqual(['637']);
    });
  });

  // ===== [10. SECURITY — 1 test] =====
  describe('Security — XSS Prevention', () => {
    it('label dengan HTML tag di-escape oleh Vue', () => {
      const wrapper = mountFormField({ label: '<script>alert("xss")</script>NIK' });
      const label = wrapper.find('label');

      // Vue otomatis escape → teks literal, bukan HTML
      expect(label.text()).toBe('<script>alert("xss")</script>NIK');
      expect(label.html()).not.toContain('<script>');
    });
  });
});