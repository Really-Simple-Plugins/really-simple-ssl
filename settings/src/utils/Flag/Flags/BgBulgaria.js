import * as React from "react";
const SvgBgBulgaria = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="BG_-_Bulgaria_svg__a"
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
    <g mask="url(#BG_-_Bulgaria_svg__a)">
      <path
        fill="#5EAA22"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="BG_-_Bulgaria_svg__b"
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
        mask="url(#BG_-_Bulgaria_svg__b)"
      >
        <path fill="#F7FCFF" d="M0 0v4h16V0H0Z" />
        <path fill="#E22C18" d="M0 8v4h16V8H0Z" />
      </g>
    </g>
  </svg>
);
export default SvgBgBulgaria;
