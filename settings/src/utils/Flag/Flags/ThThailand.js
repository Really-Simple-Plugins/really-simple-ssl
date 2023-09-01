import * as React from "react";
const SvgThThailand = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="TH_-_Thailand_svg__a"
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
    <g mask="url(#TH_-_Thailand_svg__a)">
      <path
        fill="#F50101"
        fillRule="evenodd"
        d="M0 8h16v4H0V8ZM0 0h16v3H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#3D58DB"
        stroke="#fff"
        strokeWidth={1.5}
        d="M0 3.25h-.75v5.5h17.5v-5.5H0Z"
      />
    </g>
  </svg>
);
export default SvgThThailand;
