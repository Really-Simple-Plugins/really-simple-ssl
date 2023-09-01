import * as React from "react";
const SvgChSwitzerland = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="CH_-_Switzerland_svg__a"
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
    <g mask="url(#CH_-_Switzerland_svg__a)">
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="CH_-_Switzerland_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g mask="url(#CH_-_Switzerland_svg__b)">
        <path
          fill="#F1F9FF"
          fillRule="evenodd"
          d="M9 3H7v2H5v2h2v2h2V7h2V5H9V3Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgChSwitzerland;
