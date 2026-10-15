export class RateLimiter {
  private windowStart = Date.now();
  private count = 0;

  constructor(private readonly perMinute: number) {}

  allow(): boolean {
    if (this.perMinute <= 0) return true;
    const now = Date.now();
    if (now - this.windowStart >= 60_000) {
      this.windowStart = now;
      this.count = 0;
    }
    this.count += 1;
    return this.count <= this.perMinute;
  }
}
