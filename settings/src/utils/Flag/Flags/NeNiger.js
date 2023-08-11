import * as React from "react";
const SvgNeNiger = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="NE_-_Niger_svg__a"
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
    <g mask="url(#NE_-_Niger_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="NE_-_Niger_svg__b"
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
      <g fillRule="evenodd" clipRule="evenodd" mask="url(#NE_-_Niger_svg__b)">
        <path fill="#FC6500" d="M0 0v4h16V0H0Z" />
        <path fill="#5EAA22" d="M0 8v4h16V8H0Z" />
        <path fill="#FC6500" d="M8 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
      </g>
    </g>
  </svg>
);
export default SvgNeNiger;
