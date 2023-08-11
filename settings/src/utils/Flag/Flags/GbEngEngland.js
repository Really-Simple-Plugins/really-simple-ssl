import * as React from "react";
const SvgGbEngEngland = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="GB-ENG_-_England_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#GB-ENG_-_England_svg__a)">
      <path fill="#F7FCFF" d="M0 0h16v12H0z" />
      <path
        fill="#F50302"
        fillRule="evenodd"
        d="M8.875 0H7.097v5H0v2h7.097v5h1.778V7H16V5H8.875V0Z"
        clipRule="evenodd"
      />
    </g>
  </svg>
);
export default SvgGbEngEngland;
