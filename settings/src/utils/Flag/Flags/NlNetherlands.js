import * as React from "react";
const SvgNlNetherlands = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="NL_-_Netherlands_svg__a"
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
    <g mask="url(#NL_-_Netherlands_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="NL_-_Netherlands_svg__b"
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
      <g
        fillRule="evenodd"
        clipRule="evenodd"
        mask="url(#NL_-_Netherlands_svg__b)"
      >
        <path fill="#E31D1C" d="M0 0v4h16V0H0Z" />
        <path fill="#3D58DB" d="M0 8v4h16V8H0Z" />
      </g>
    </g>
  </svg>
);
export default SvgNlNetherlands;
