import {Composition} from 'remotion';
import {PractiqProductDemo} from './PractiqProductDemo.jsx';
import {SCENES, TOTAL_FRAMES} from './scene-data.js';

export const RemotionRoot = () => {
	return (
		<>
			<Composition
				id="PractiqProductDemo"
				component={PractiqProductDemo}
				durationInFrames={TOTAL_FRAMES}
				fps={30}
				width={1920}
				height={1080}
				defaultProps={{scenes: SCENES}}
			/>
		</>
	);
};
