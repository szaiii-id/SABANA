export function getSafeErrorMessage(errorMessage: string | null | undefined): string {
  if (!errorMessage || !errorMessage.trim()) return '';
  const err = errorMessage.toLowerCase();
  
  const isTechnicalError = (
    err.includes('sql') || 
    err.includes('exception') || 
    err.includes('typeerror') || 
    err.includes('undefined') ||
    err.includes('stack trace') ||
    err.includes('syntax error') ||
    err.includes('internal server error') ||
    err.includes('could not connect') ||
    err.includes('connection refused') ||
    err.includes('timed out')
  );
  
  if (isTechnicalError) {
    return 'Layanan sedang sibuk atau terjadi gangguan sistem. Silakan coba beberapa saat lagi.';
  }
  
  return errorMessage.trim();
}