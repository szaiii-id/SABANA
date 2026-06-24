export function formatDuration(seconds: number): string {
  if (seconds >= 60) {
    const minutes = Math.round(seconds / 60);
    return `${minutes} menit`;
  }
  return `${seconds} detik`;
}