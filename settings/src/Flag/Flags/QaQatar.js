import * as React from "react";
const SvgQaQatar = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="QA_-_Qatar_svg__a"
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
    <g mask="url(#QA_-_Qatar_svg__a)">
      <path fill="#B61C49" d="M0 0h16v12H0z" />
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0h3.4L6 1 3.4 2 6 3 3.4 4 6 5 3.4 6 6 7 3.4 8 6 9l-2.6 1L6 11l-2.6 1H0V0Z"
        clipRule="evenodd"
      />
    </g>
  </svg>
);
export default SvgQaQatar;
