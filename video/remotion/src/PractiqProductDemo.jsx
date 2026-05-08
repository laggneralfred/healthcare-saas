import {
	AbsoluteFill,
	Easing,
	interpolate,
	spring,
	staticFile,
	useCurrentFrame,
	useVideoConfig,
	Img,
	Sequence,
} from 'remotion';

const palette = {
	background: '#f6f3eb',
	ink: '#0f172a',
	subtle: '#475569',
	line: '#d7d4cb',
	teal: '#0f766e',
	tealDark: '#134e4a',
	panel: '#fffdf8',
	gold: '#c48c24',
};

const Frame = ({children}) => (
	<AbsoluteFill
		style={{
			background: `radial-gradient(circle at top left, rgba(15,118,110,0.12), transparent 30%), linear-gradient(135deg, ${palette.background} 0%, #ffffff 100%)`,
			color: palette.ink,
			padding: 72,
			fontFamily: '"Instrument Sans", "Aptos", "Segoe UI", sans-serif',
		}}
	>
		<div
			style={{
				position: 'absolute',
				inset: 28,
				borderRadius: 36,
				border: `1px solid ${palette.line}`,
				opacity: 0.85,
			}}
		/>
		{children}
	</AbsoluteFill>
);

const PlaceholderVisual = ({scene}) => {
	return (
		<div
			style={{
				minHeight: 676,
				display: 'flex',
				flexDirection: 'column',
				justifyContent: 'center',
				alignItems: 'center',
				padding: 48,
				background: 'linear-gradient(160deg, #f8faf7 0%, #eef5f2 100%)',
				color: palette.ink,
				textAlign: 'center',
			}}
		>
			<div
				style={{
					padding: '10px 18px',
					borderRadius: 999,
					backgroundColor: '#dff7f3',
					color: palette.tealDark,
					fontSize: 22,
					fontWeight: 700,
				}}
			>
				Placeholder
			</div>
			<div style={{marginTop: 24, fontSize: 42, fontWeight: 800, lineHeight: 1.15}}>
				Replace with a Practiq screenshot for
				<br />
				{scene.label}
			</div>
			<div style={{marginTop: 18, fontSize: 24, lineHeight: 1.45, color: palette.subtle, maxWidth: 560}}>
				This scene can preview without an image source. Add a screenshot later by placing a file in
				` public/remotion-demo/screenshots/ ` and setting the filename in `scene-data.js`.
			</div>
		</div>
	);
};

const Scene = ({scene}) => {
	const frame = useCurrentFrame();
	const {fps} = useVideoConfig();
	const assetSrc = scene.asset ? staticFile(`remotion-demo/screenshots/${scene.asset}`) : null;

	const rise = spring({
		fps,
		frame,
		config: {
			damping: 18,
			mass: 0.8,
			stiffness: 110,
		},
	});

	const fade = interpolate(frame, [0, 18, scene.durationInFrames - 24, scene.durationInFrames], [0, 1, 1, 0], {
		easing: Easing.out(Easing.ease),
		extrapolateLeft: 'clamp',
		extrapolateRight: 'clamp',
	});

	const assetScale = interpolate(frame, [0, 26], [0.96, 1], {extrapolateRight: 'clamp'});

	return (
		<Frame>
			<div
				style={{
					display: 'grid',
					gridTemplateColumns: '1.05fr 0.95fr',
					gap: 44,
					height: '100%',
					alignItems: 'center',
					opacity: fade,
					transform: `translateY(${(1 - rise) * 32}px)`,
				}}
			>
				<div style={{display: 'flex', flexDirection: 'column', gap: 22}}>
					<div
						style={{
							display: 'inline-flex',
							alignSelf: 'flex-start',
							padding: '10px 18px',
							borderRadius: 999,
							backgroundColor: '#dff7f3',
							color: palette.tealDark,
							fontSize: 24,
							fontWeight: 700,
							letterSpacing: 0.3,
						}}
					>
						{scene.label}
					</div>
					<h1
						style={{
							fontSize: 72,
							lineHeight: 1.03,
							fontWeight: 800,
							margin: 0,
							maxWidth: 860,
						}}
					>
						{scene.title}
					</h1>
					<p
						style={{
							fontSize: 31,
							lineHeight: 1.45,
							margin: 0,
							color: palette.subtle,
							maxWidth: 860,
						}}
					>
						{scene.body}
					</p>
					<div style={{display: 'flex', flexDirection: 'column', gap: 14, marginTop: 4}}>
						{scene.bullets.map((bullet, index) => {
							const bulletFade = interpolate(frame, [10 + index * 6, 28 + index * 6], [0, 1], {
								extrapolateLeft: 'clamp',
								extrapolateRight: 'clamp',
							});

							return (
								<div
									key={bullet}
									style={{
										display: 'flex',
										alignItems: 'center',
										gap: 16,
										opacity: bulletFade,
										transform: `translateX(${(1 - bulletFade) * -18}px)`,
									}}
								>
									<div
										style={{
											width: 14,
											height: 14,
											borderRadius: 999,
											backgroundColor: index === 1 ? palette.gold : palette.teal,
											flexShrink: 0,
										}}
									/>
									<div style={{fontSize: 28, color: palette.ink, fontWeight: 600}}>{bullet}</div>
								</div>
							);
						})}
					</div>
					<div
						style={{
							marginTop: 18,
							padding: '18px 22px',
							borderRadius: 24,
							backgroundColor: '#ffffff',
							border: `1px solid ${palette.line}`,
							boxShadow: '0 16px 40px rgba(15, 23, 42, 0.06)',
							fontSize: 24,
							lineHeight: 1.45,
							color: palette.tealDark,
							fontWeight: 700,
							maxWidth: 840,
						}}
					>
						{scene.callout}
					</div>
				</div>

				<div
					style={{
						display: 'flex',
						flexDirection: 'column',
						gap: 18,
						alignItems: 'stretch',
						transform: `scale(${assetScale})`,
					}}
				>
					<div
						style={{
							borderRadius: 34,
							overflow: 'hidden',
							backgroundColor: '#ffffff',
							border: `1px solid ${palette.line}`,
							boxShadow: '0 24px 60px rgba(15, 23, 42, 0.08)',
						}}
					>
						{assetSrc ? (
							<Img src={assetSrc} style={{width: '100%', display: 'block'}} />
						) : (
							<PlaceholderVisual scene={scene} />
						)}
					</div>
					<div
						style={{
							display: 'flex',
							justifyContent: 'space-between',
							alignItems: 'center',
							padding: '16px 20px',
							borderRadius: 22,
							backgroundColor: palette.panel,
							border: `1px solid ${palette.line}`,
							fontSize: 22,
							color: palette.subtle,
						}}
					>
						<span>Placeholder visual - replace with real product screenshot later</span>
						<span style={{fontWeight: 700, color: palette.tealDark}}>{scene.slug}.png</span>
					</div>
				</div>
			</div>
		</Frame>
	);
};

export const PractiqProductDemo = ({scenes}) => {
	let from = 0;

	return (
		<AbsoluteFill style={{backgroundColor: palette.background}}>
			{scenes.map((scene) => {
				const start = from;
				from += scene.durationInFrames;

				return (
					<Sequence key={scene.slug} from={start} durationInFrames={scene.durationInFrames}>
						<Scene scene={scene} />
					</Sequence>
				);
			})}
		</AbsoluteFill>
	);
};
