
export function getSafeErrorMessage(errorMessage: string | null | undefined): string {
  if (!errorMessage) return '';
  const err = errorMessage.toLowerCase();
  
  if (
    err.includes('sql') || 
    err.includes('exception') || 
    err.includes('typeerror') || 
    err.includes('server error') || 
    err.includes('undefined') || 
    errorMessage.length > 80
  ) {
    return 'Layanan sedang sibuk atau terjadi gangguan sistem. Silakan coba beberapa saat lagi.';
  }
  
  return errorMessage;
}