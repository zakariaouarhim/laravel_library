# Lessons

## 2026-07-15 — Image "zoom/crop" features: ask for the target shape first
Built a uniform zoom (same crop factor both axes) for cutting white margins off
covers; the admin actually wanted to reshape squarish sources into rectangular
book covers, which needs independent width/height cropping. Uniform zoom can
never change the aspect ratio, so it couldn't solve the real problem.

**Rule:** when an admin asks to "zoom/crop" an image, the requirement is almost
always a *target shape* (book-cover rectangle), not magnification. Ask "what
should the final image look like?" before choosing the crop model, and prefer
per-axis controls over a single uniform factor.
