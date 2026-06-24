import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AdminAuthLayout from '../AdminAuthLayout.vue';

// =============================================
// TEST SUITE
// =============================================
describe('AdminAuthLayout', () => {

  // ===== HAPPY PATH =====

  it('test_render_slot_content', () => {
    const wrapper = mount(AdminAuthLayout, {
      slots: { default: '<div class="test-content">Login Form</div>' },
    });

    expect(wrapper.text()).toContain('Login Form');
    expect(wrapper.find('.test-content').exists()).toBe(true);
  });

  it('test_render_dengan_default_maxWidth_md', () => {
    const wrapper = mount(AdminAuthLayout, {
      slots: { default: '<div>Content</div>' },
    });

    const card = wrapper.find('.max-w-md');
    expect(card.exists()).toBe(true);
  });

  // ===== SAD PATH =====

  it('test_render_tanpa_slot_tidak_error', () => {
    const wrapper = mount(AdminAuthLayout);

    expect(wrapper.find('.bg-white\\/95').exists()).toBe(true);
  });

  // ===== BOUNDARY =====

  it('test_maxWidth_xl', () => {
    const wrapper = mount(AdminAuthLayout, {
      props: { maxWidth: 'xl' },
      slots: { default: '<div>Content</div>' },
    });

    expect(wrapper.find('.max-w-xl').exists()).toBe(true);
  });

  it('test_maxWidth_lg', () => {
    const wrapper = mount(AdminAuthLayout, {
      props: { maxWidth: 'lg' },
      slots: { default: '<div>Content</div>' },
    });

    expect(wrapper.find('.max-w-lg').exists()).toBe(true);
  });

  it('test_maxWidth_md_default', () => {
    const wrapper = mount(AdminAuthLayout, {
      props: { maxWidth: 'md' },
      slots: { default: '<div>Content</div>' },
    });

    expect(wrapper.find('.max-w-md').exists()).toBe(true);
  });

  it('test_maxWidth_tidak_valid_fallback_md', () => {
    const wrapper = mount(AdminAuthLayout, {
      props: { maxWidth: 'xxl' },
      slots: { default: '<div>Content</div>' },
    });

    expect(wrapper.find('.max-w-md').exists()).toBe(true);
  });

  // ===== EDGE CASE =====

  it('test_extraPadding_true_tambah_padding', () => {
    const wrapper = mount(AdminAuthLayout, {
      props: { extraPadding: true },
      slots: { default: '<div>Content</div>' },
    });

    const card = wrapper.find('.bg-white\\/95');
    expect(card.classes()).toContain('mt-10');
    expect(card.classes()).toContain('mb-10');
  });

  it('test_extraPadding_false_default', () => {
    const wrapper = mount(AdminAuthLayout, {
      props: { extraPadding: false },
      slots: { default: '<div>Content</div>' },
    });

    const card = wrapper.find('.bg-white\\/95');
    expect(card.classes()).not.toContain('mt-10');
  });

  // ===== NULL / EMPTY =====

  it('test_props_tidak_diberikan_gunakan_default', () => {
    const wrapper = mount(AdminAuthLayout);

    expect(wrapper.find('.max-w-md').exists()).toBe(true);
  });

  // ===== DATA TYPE =====

  it('test_maxWidth_string_selain_valid_fallback_md', () => {
    const wrapper = mount(AdminAuthLayout, {
      props: { maxWidth: '' },
      slots: { default: '<div>Content</div>' },
    });

    expect(wrapper.find('.max-w-md').exists()).toBe(true);
  });

  // ===== STATE TRANSITION =====

  it('test_render_responsif_tanpa_error', () => {
    const wrapper = mount(AdminAuthLayout, {
      slots: { default: '<div>Content</div>' },
    });

    expect(wrapper.find('.min-h-screen').exists()).toBe(true);
    expect(wrapper.find('.flex').exists()).toBe(true);
  });

  // ===== RENDERING =====

  it('test_render_background_ornaments', () => {
    const wrapper = mount(AdminAuthLayout);

    const ornaments = wrapper.findAll('.animate-blob');
    expect(ornaments.length).toBe(3);
  });

  it('test_render_card_container', () => {
    const wrapper = mount(AdminAuthLayout);

    expect(wrapper.find('.bg-white\\/95').exists()).toBe(true);
    expect(wrapper.find('.rounded-\\[3rem\\]').exists()).toBe(true);
    expect(wrapper.find('.backdrop-blur-2xl').exists()).toBe(true);
  });

  it('test_render_border_styling', () => {
    const wrapper = mount(AdminAuthLayout);

    const card = wrapper.find('.border-\\[\\#E8D5C4\\]');
    expect(card.exists()).toBe(true);
  });

  it('test_render_shadow_styling', () => {
    const wrapper = mount(AdminAuthLayout);

    const card = wrapper.find('.shadow-\\[0_30px_80px_rgba\\(27\\,67\\,50\\,0\\.1\\)\\]');
    expect(card.exists()).toBe(true);
  });
});