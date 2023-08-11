import * as React from "react";
const SvgSeSweden = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="SE_-_Sweden_svg__a"
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
    <g mask="url(#SE_-_Sweden_svg__a)">
      <path
        fill="#3D58DB"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="SE_-_Sweden_svg__b"
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
      <g mask="url(#SE_-_Sweden_svg__b)">
        <path
          fill="#FECA00"
          fillRule="evenodd"
          d="M5 0h2v5h9v2H7v5H5V7H0V5h5V0Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgSeSweden;
