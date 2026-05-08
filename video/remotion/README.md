# Practiq Product Demo Video

This folder contains a Remotion scaffold for a 90 to 120 second Practiq product demo.

Theme:

`Documentation-first software for practices that put care before billing.`

The current composition is built with placeholder visuals so it can be reviewed without a live demo account or real patient data.

## Included composition

- `PractiqProductDemo`
- Format: `1920x1080`
- Frame rate: `30fps`
- Duration: about `96 seconds`

## Preview the video

From the repo root:

```bash
npm run video:demo
```

To check that the composition loads without opening the studio:

```bash
npm run video:demo:check
```

## Replace placeholder screenshots

Placeholder visuals live in:

- `public/remotion-demo/screenshots/`

Each scene already points at a named file such as:

- `01-problem.svg`
- `03-setup-checklist.svg`
- `06-calendar-context.svg`
- `11-reports-exports.svg`

To replace a placeholder:

1. Export a clean product screenshot for that scene.
2. Keep the same filename, or update the asset filename in `video/remotion/src/scene-data.js`.
3. Prefer `PNG` screenshots at roughly `1600px` wide or larger.
4. Do not use real patient data.

The rendered video also shows the expected replacement filename on screen to make swaps easy.

## Render MP4

From the repo root:

```bash
npm run video:demo:render
```

Default output:

```text
video/remotion/out/practiq-product-demo.mp4
```

## Edit the story

Main files:

- `video/remotion/src/scene-data.js`
- `video/remotion/src/PractiqProductDemo.jsx`
- `video/remotion/src/Root.jsx`

`scene-data.js` controls:

- scene order
- scene titles and captions
- on-screen bullets
- placeholder asset mapping
- timing per scene

## Messaging guardrails

- Use `appointment requests with staff confirmation`, not direct online booking.
- Do not claim full accounting software.
- Do not claim clinic patient payment processing through Stripe.
- If Stripe is mentioned, keep it limited to Practiq subscription billing readiness.
- Keep AI positioning optional, reviewed, and practitioner-controlled.
