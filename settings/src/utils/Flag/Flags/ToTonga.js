import * as React from "react";
const SvgToTonga = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="TO_-_Tonga_svg__a"
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
    <g mask="url(#TO_-_Tonga_svg__a)">
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="TO_-_Tonga_svg__b"
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
      <g mask="url(#TO_-_Tonga_svg__b)">
        <path fill="#F7FCFF" d="M0 0h9v8H0z" />
        <path
          fill="#E31D1C"
          fillRule="evenodd"
          d="M6 1H4v2H2v2h2v2h2V5h2V3H6V1Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgToTonga;
