import * as React from "react";
const SvgVnVietnam = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="VN_-_Vietnam_svg__a"
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
    <g mask="url(#VN_-_Vietnam_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0h16v12H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="VN_-_Vietnam_svg__b"
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
      <g mask="url(#VN_-_Vietnam_svg__b)">
        <path
          fill="#FFD221"
          fillRule="evenodd"
          d="M8.031 7.99 5.456 9.627l.863-2.866-1.837-1.873 2.533-.055 1.12-2.83L9.157 4.87l2.526.044-1.899 1.907.887 2.727-2.64-1.558Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgVnVietnam;
